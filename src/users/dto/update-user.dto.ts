// src/users/dto/update-user.dto.ts
import { PartialType } from '@nestjs/mapped-types';
import { CreateUserDto } from './create-user.dto';
import { IsBoolean } from 'class-validator';

// PartialType makes all properties of CreateUserDto optional.
// This is perfect for update operations where you might only want to change one field.
export class UpdateUserDto extends PartialType(CreateUserDto) {
  @IsBoolean()
  is_active?: boolean;
}
