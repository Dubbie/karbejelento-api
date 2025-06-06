import {
  IsEmail,
  IsInt,
  IsNotEmpty,
  IsOptional,
  IsString,
} from 'class-validator';

export class CreateNotifierDto {
  @IsInt()
  @IsNotEmpty()
  customer_id: number;

  @IsString()
  @IsNotEmpty()
  name: string;

  @IsEmail()
  email: string;

  @IsString()
  @IsOptional()
  phone_number?: string;
}
