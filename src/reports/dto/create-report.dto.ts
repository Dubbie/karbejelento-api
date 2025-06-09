import { Type } from 'class-transformer';
import {
  IsDate,
  IsEnum,
  IsInt,
  IsNotEmpty,
  IsOptional,
  IsString,
} from 'class-validator';
import {
  ClaimantType,
  DamageType,
  EstimatedCost,
} from '../entities/report.entity';

export class CreateReportDto {
  @IsInt()
  @IsNotEmpty()
  building_id: number;

  @IsInt()
  @IsNotEmpty()
  notifier_id: number;

  @IsEnum(DamageType)
  @IsNotEmpty()
  damage_type: DamageType;

  @IsEnum(EstimatedCost)
  @IsNotEmpty()
  estimated_cost: EstimatedCost;

  @IsString()
  @IsNotEmpty()
  damage_description: string;

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

  @IsString()
  @IsOptional()
  claimant_phone_number?: string;

  // Optional fields for building claimant
  @IsString()
  @IsOptional()
  building_account_number?: string;

  @IsString()
  contact_name: string;

  @IsString()
  contact_phone_number: string;

  // Damage location
  @IsString()
  @IsOptional()
  damaged_building_name?: string;

  @IsString()
  damaged_building_number: string;

  @IsString()
  damaged_floor: string;

  @IsString()
  @IsOptional()
  damaged_unit_or_door?: string;
}
