import { IsInt, IsNotEmpty, IsString } from 'class-validator';

export class CreateBuildingDto {
  @IsString()
  @IsNotEmpty()
  name: string;

  @IsString()
  @IsNotEmpty()
  postcode: string;

  @IsString()
  @IsNotEmpty()
  city: string;

  @IsString()
  @IsNotEmpty()
  street_name: string;

  @IsString()
  @IsNotEmpty()
  street_number: string;

  @IsString()
  @IsNotEmpty()
  bond_number: string;

  @IsString()
  @IsNotEmpty()
  account_number: string;

  @IsString()
  @IsNotEmpty()
  insurer: string;

  // This is required to assign the building to a customer upon creation
  @IsInt()
  @IsNotEmpty()
  customer_id: number;
}
