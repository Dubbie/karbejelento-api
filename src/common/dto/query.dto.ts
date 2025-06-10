import { IsNumber, IsOptional, IsString, Min } from 'class-validator';
import { Type } from 'class-transformer';

export class QueryDto {
  @IsOptional()
  @Type(() => Number)
  @IsNumber()
  @Min(1)
  page?: number = 1;

  @IsOptional()
  @Type(() => Number)
  @IsNumber()
  @Min(1)
  limit?: number = 10;

  /**
   * Sorting parameter.
   * Format: "fieldName:direction" (e.g., "name:ASC", "createdAt:DESC")
   */
  @IsOptional()
  @IsString()
  sort?: string;

  /**
   * Filtering parameter.
   * Format: "fieldName:operator:value" (e.g., "name:like:%John%", "isActive:eq:true")
   * Multiple filters can be sent as an array: ?filter=name:like:%John%&filter=status:eq:new
   */
  @IsOptional()
  filter?: string | string[];
}
