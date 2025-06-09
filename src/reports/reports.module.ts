import { Module } from '@nestjs/common';
import { ReportsService } from './reports.service';
import { ReportsController } from './reports.controller';
import { TypeOrmModule } from '@nestjs/typeorm';
import { Report } from './entities/report.entity';
import { ReportStatusHistory } from './entities/report-status-history.entity';
import { BuildingsModule } from 'src/buildings/buildings.module';
import { ReportAttachment } from './entities/report-attachment.entity';

@Module({
  imports: [
    TypeOrmModule.forFeature([Report, ReportStatusHistory, ReportAttachment]),
    BuildingsModule,
  ],
  controllers: [ReportsController],
  providers: [ReportsService],
})
export class ReportsModule {}
