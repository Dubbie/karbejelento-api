// src/reports/reports.service.ts

import { Injectable, NotFoundException } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { BuildingsService } from 'src/buildings/buildings.service';
import { User } from 'src/users/entities/user.entity';
import { DataSource, Repository } from 'typeorm';
import { CreateReportDto } from './dto/create-report.dto';
import { Report, ReportStatus } from './entities/report.entity';
import { v4 as uuidv4 } from 'uuid';
import { ReportStatusHistory } from './entities/report-status-history.entity';
import { UpdateReportDto } from './dto/update-report.dto';

@Injectable()
export class ReportsService {
  constructor(
    @InjectRepository(Report)
    private readonly reportRepository: Repository<Report>,
    @InjectRepository(ReportStatusHistory)
    private readonly statusHistoryRepository: Repository<ReportStatusHistory>,
    private readonly buildingService: BuildingsService,
    private readonly dataSource: DataSource,
  ) {}

  async create(
    createReportDto: CreateReportDto,
    user: Omit<User, 'password_hash'>,
  ): Promise<Report> {
    const { building_id, ...reportData } = createReportDto;

    // 1. Find the associated building to snapshot its data
    const building = await this.buildingService.findOneById(building_id); // We need to add this method

    const queryRunner = this.dataSource.createQueryRunner();
    await queryRunner.connect();
    await queryRunner.startTransaction();

    try {
      // 2. Create the Report entity
      const newReport = this.reportRepository.create({
        ...reportData,
        uuid: uuidv4(),
        building_id: building.id,
        created_by_user_id: user.id,
        // Snapshot the data from the building
        bond_number: building.bond_number,
        insurer: building.insurer,
      });
      const savedReport = await queryRunner.manager.save(newReport);

      // 3. Create the initial status history record
      const initialStatus = this.statusHistoryRepository.create({
        report_id: savedReport.id,
        status: ReportStatus.NEW,
        user_id: user.id,
        notes: 'Report created.',
      });
      await queryRunner.manager.save(initialStatus);

      await queryRunner.commitTransaction();
      return savedReport;
    } catch (err) {
      await queryRunner.rollbackTransaction();
      throw err;
    } finally {
      await queryRunner.release();
    }
  }

  // NOTE: For a real app, findAll, findOne, update, and remove would
  // require complex authorization logic based on the user's role and
  // their relationship to the building/customer. We will keep it simple here.

  findAll(): Promise<Report[]> {
    return this.reportRepository.find({ relations: { building: true } });
  }

  async findOneByUuid(uuid: string): Promise<Report> {
    const report = await this.reportRepository.findOneBy({ uuid });
    if (!report) {
      throw new NotFoundException(`Report with UUID ${uuid} not found`);
    }
    return report;
  }

  async update(
    uuid: string,
    updateReportDto: UpdateReportDto,
  ): Promise<Report> {
    const report = await this.reportRepository.preload({
      uuid,
      ...updateReportDto,
    });
    if (!report) {
      throw new NotFoundException(`Report with UUID ${uuid} not found`);
    }
    return this.reportRepository.save(report);
  }
}
