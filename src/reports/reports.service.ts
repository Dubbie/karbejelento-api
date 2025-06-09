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
import {
  AttachmentCategory,
  ReportAttachment,
} from './entities/report-attachment.entity';

@Injectable()
export class ReportsService {
  constructor(
    @InjectRepository(Report)
    private readonly reportRepository: Repository<Report>,
    @InjectRepository(ReportStatusHistory)
    private readonly statusHistoryRepository: Repository<ReportStatusHistory>,
    @InjectRepository(ReportAttachment)
    private readonly attachmentRepository: Repository<ReportAttachment>,
    private readonly buildingService: BuildingsService,
    private readonly dataSource: DataSource,
  ) {}

  async create(
    createReportDto: CreateReportDto,
    user: Omit<User, 'password_hash'>,
  ): Promise<Report> {
    const { building_id, ...reportData } = createReportDto;

    // 1. Find the associated building to snapshot its data
    const building = await this.buildingService.findOneById(building_id);

    const queryRunner = this.dataSource.createQueryRunner();
    await queryRunner.connect();
    await queryRunner.startTransaction();

    try {
      // 2. Create the Report entity
      const newReport = this.reportRepository.create({
        ...reportData,
        uuid: uuidv4(),
        building: building,
        notifier: { id: reportData.notifier_id },
        created_by: user,
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

  findAll(): Promise<Report[]> {
    return this.reportRepository.find({
      relations: { building: true, created_by: true },
    });
  }

  async findOneByUuid(uuid: string): Promise<Report> {
    const report = await this.reportRepository.find({
      where: { uuid },
      relations: {
        building: {
          management_history: {
            customer: {
              manager: true,
            },
          },
        },
        created_by: true,
        notifier: true,
      },
    });

    if (!report) {
      throw new NotFoundException(`Report with UUID ${uuid} not found`);
    }

    return report[0];
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

  async addAttachments(
    reportUuid: string,
    files: Array<Express.Multer.File>,
    categories: AttachmentCategory[],
    user: Omit<User, 'password_hash'>,
  ): Promise<ReportAttachment[]> {
    // 1. Find the report the attachments belong to
    const report = await this.findOneByUuid(reportUuid);

    // 2. Create an array of ReportAttachment entities from the uploaded file data
    const attachments = files.map((file, index) => {
      return this.attachmentRepository.create({
        uuid: uuidv4(),
        report_id: report.id,
        uploaded_by_user_id: user.id,
        file_path: file.path,
        file_name_original: file.originalname,
        file_mime_type: file.mimetype,
        file_size_bytes: file.size,
        category: categories[index] || 'other',
      });
    });

    // 3. Save all the new attachment records to the database
    return this.attachmentRepository.save(attachments);
  }
}
