import { Injectable, NotFoundException } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { DataSource, Repository } from 'typeorm';
import { Building } from './entities/building.entity';
import { CreateBuildingDto } from './dto/create-building.dto';
import { UpdateBuildingDto } from './dto/update-building.dto';
import { v4 as uuidv4 } from 'uuid';
import { BuildingManagement } from './entities/building-management.entity';

@Injectable()
export class BuildingsService {
  constructor(
    @InjectRepository(Building)
    private readonly buildingRepository: Repository<Building>,
    @InjectRepository(BuildingManagement)
    private readonly managementRepository: Repository<BuildingManagement>,
    private readonly dataSource: DataSource, // Inject DataSource for transactions
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
        building_id: savedBuilding.id,
        customer_id: customer_id,
        start_date: new Date(), // Starts now
        end_date: null, // No end date means it's the current management
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

  findAll(): Promise<Building[]> {
    return this.buildingRepository.find();
  }

  async findOneByUuid(uuid: string): Promise<Building> {
    const building = await this.buildingRepository.findOneBy({ uuid });
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
}
