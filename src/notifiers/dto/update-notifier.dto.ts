import { PartialType } from '@nestjs/mapped-types';
import { CreateNotifierDto } from './create-notifier.dto';

// customer_id cannot be changed via this DTO
export class UpdateNotifierDto extends PartialType(CreateNotifierDto) {}
