// src/users/dto/update-user.dto.ts
import { PartialType } from '@nestjs/mapped-types';
import { CreateUserDto } from './create-user.dto';

// PartialType makes all properties of CreateUserDto optional.
// This is perfect for update operations where you might only want to change one field.
export class UpdateUserDto extends PartialType(CreateUserDto) {}
