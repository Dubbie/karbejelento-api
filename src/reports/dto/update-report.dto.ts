import { IsNumber, IsOptional, IsString } from 'class-validator';
import { PartialType } from '@nestjs/mapped-types';
import { CreateReportDto } from './create-report.dto';

export class UpdateReportDto extends PartialType(CreateReportDto) {
  @IsString()
  @IsOptional()
  damage_id?: string;

  @IsNumber()
  @IsOptional()
  estimated_cost?: number;
}
