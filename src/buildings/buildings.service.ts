import { Injectable, NotFoundException } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { DataSource, Repository } from 'typeorm';
import { Building } from './entities/building.entity';
import { CreateBuildingDto } from './dto/create-building.dto';
import { UpdateBuildingDto } from './dto/update-building.dto';
import { v4 as uuidv4 } from 'uuid';
import { BuildingManagement } from './entities/building-management.entity';
import { UsersService } from 'src/users/users.service';
import { Notifier } from 'src/notifiers/entities/notifier.entity';
import { User } from 'src/users/entities/user.entity';
import { QueryScopeService } from 'src/common/services/query-scope.service';
import { QueryDto } from 'src/common/dto/query.dto';
import { PaginationService } from 'src/common/services/pagination.service';
import { PaginationResult } from 'src/common/interfaces/pagination-result.interface';

@Injectable()
export class BuildingsService {
  constructor(
    @InjectRepository(Building)
    private readonly buildingRepository: Repository<Building>,
    @InjectRepository(BuildingManagement)
    private readonly managementRepository: Repository<BuildingManagement>,
    private readonly usersService: UsersService,
    private readonly dataSource: DataSource,
    private readonly queryScopeService: QueryScopeService,
    private readonly paginationService: PaginationService,
  ) {}

  /**
   * Creates a new building AND its initial management record in a single transaction.
   */
  async create(createBuildingDto: CreateBuildingDto): Promise<Building> {
    const { customer_id, ...buildingData } = createBuildingDto;

    // Use a transaction to ensure both operations succeed or fail together
    const queryRunner = this.dataSource.createQueryRunner();
    await queryRunner.connect();
    await queryRunner.startTransaction();

    try {
      // 1. Create the building
      const newBuilding = this.buildingRepository.create({
        ...buildingData,
        uuid: uuidv4(),
      });
      const savedBuilding = await queryRunner.manager.save(newBuilding);

      // 2. Create the initial management record
      const newManagement = this.managementRepository.create({
        building: savedBuilding,
        customer: { id: customer_id },
        start_date: new Date(),
        end_date: null,
      });
      await queryRunner.manager.save(newManagement);

      await queryRunner.commitTransaction();
      return savedBuilding;
    } catch (err) {
      // If anything fails, roll back the entire transaction
      await queryRunner.rollbackTransaction();
      throw err; // Re-throw the error to be handled by NestJS
    } finally {
      // Always release the query runner
      await queryRunner.release();
    }
  }

  findAll(user: User, queryDto: QueryDto): Promise<PaginationResult<Building>> {
    const qb = this.buildingRepository
      .createQueryBuilder('building')
      .leftJoinAndSelect('building.management_history', 'management_history')
      .leftJoinAndSelect('management_history.customer', 'customer');

    this.queryScopeService.applyBuildingScope(qb, user, 'building');

    return this.paginationService.paginate(qb, queryDto, 'building', {
      sortableFields: ['name'],
      filterableFields: ['name'],
    });
  }

  async findOneByUuid(uuid: string): Promise<Building> {
    const building = await this.buildingRepository.findOne({
      where: { uuid },
      relations: {
        management_history: {
          customer: true,
        },
      },
    });

    if (!building) {
      throw new NotFoundException(`Building with UUID ${uuid} not found`);
    }
    return building;
  }

  async update(
    uuid: string,
    updateBuildingDto: UpdateBuildingDto,
  ): Promise<Building> {
    const building = await this.buildingRepository.preload({
      uuid: uuid,
      ...updateBuildingDto,
    });

    if (!building) {
      throw new NotFoundException(`Building with UUID ${uuid} not found`);
    }
    return this.buildingRepository.save(building);
  }

  async remove(uuid: string): Promise<void> {
    const result = await this.buildingRepository.delete({ uuid });
    if (result.affected === 0) {
      throw new NotFoundException(`Building with UUID ${uuid} not found`);
    }
  }

  async findOneById(id: number): Promise<Building> {
    const building = await this.buildingRepository.findOneBy({ id });
    if (!building) {
      throw new NotFoundException(`Building with ID ${id} not found`);
    }
    return building;
  }

  /**
   * Finds all available notifiers for a building based on its current customer.
   * @param buildingId The ID of the building
   * @returns An array of Notifier entities.
   */
  async findNotifiersForBuilding(buildingId: number): Promise<Notifier[]> {
    // 1. Find the building and its current customer
    // We don't need the full management history here, so we can optimize the query
    const building = await this.buildingRepository.findOne({
      where: { id: buildingId },
      relations: {
        management_history: {
          customer: true, // We only need the customer from the history
        },
      },
    });

    if (!building) {
      throw new NotFoundException(`Building with ID ${buildingId} not found`);
    }

    // 2. The @AfterLoad hook has already populated `current_customer` for us
    const currentCustomer = building.current_customer;

    if (!currentCustomer) {
      // If there is no current customer, there are no notifiers
      return [];
    }

    // 3. Use the UsersService to get that customer's notifiers
    const customerWithNotifiers =
      await this.usersService.findNotifiersForCustomer(currentCustomer.id);

    // 4. Return just the array of notifiers
    return customerWithNotifiers.notifiers;
  }
}
