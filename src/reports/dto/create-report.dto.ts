import { Type } from 'class-transformer';
import {
  IsDate,
  IsEnum,
  IsInt,
  IsNotEmpty,
  IsOptional,
  IsString,
  MaxLength,
} from 'class-validator';
import { ClaimantType } from '../entities/report.entity';

export class CreateReportDto {
  @IsInt()
  @IsNotEmpty()
  building_id: number;

  @IsInt()
  @IsOptional()
  notifier_id?: number;

  @IsString()
  @IsNotEmpty()
  @MaxLength(255)
  damage_type: string;

  @IsString()
  @IsNotEmpty()
  damage_location_description: string;

  @Type(() => Date)
  @IsDate()
  damage_date: Date;

  @IsEnum(ClaimantType)
  @IsNotEmpty()
  claimant_type: ClaimantType;

  // Optional fields for resident claimant
  @IsString()
  @IsOptional()
  claimant_name?: string;

  @IsString()
  @IsOptional()
  claimant_email?: string;
}
