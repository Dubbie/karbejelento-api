import {
  Controller,
  Get,
  Post,
  Body,
  Patch,
  Param,
  Delete,
  ParseIntPipe,
  UseGuards,
} from '@nestjs/common';
import { NotifiersService } from './notifiers.service';
import { CreateNotifierDto } from './dto/create-notifier.dto';
import { UpdateNotifierDto } from './dto/update-notifier.dto';
import { AuthGuard } from '@nestjs/passport';
import { RolesGuard } from 'src/auth/guards/roles.guard';
import { Roles } from 'src/auth/decorators/roles.decorator';
import { UserRole } from 'src/users/entities/user.entity';

@Controller('notifiers')
@UseGuards(AuthGuard('jwt'), RolesGuard)
export class NotifiersController {
  constructor(private readonly notifiersService: NotifiersService) {}

  @Post()
  @Roles(UserRole.ADMIN, UserRole.MANAGER)
  create(@Body() createNotifierDto: CreateNotifierDto) {
    return this.notifiersService.create(createNotifierDto);
  }

  @Get()
  findAll() {
    return this.notifiersService.findAll();
  }

  @Get(':id')
  findOne(@Param('id', ParseIntPipe) id: number) {
    return this.notifiersService.findOne(id);
  }

  @Patch(':id')
  @Roles(UserRole.ADMIN, UserRole.MANAGER)
  update(
    @Param('id', ParseIntPipe) id: number,
    @Body() updateNotifierDto: UpdateNotifierDto,
  ) {
    return this.notifiersService.update(id, updateNotifierDto);
  }

  @Delete(':id')
  @Roles(UserRole.ADMIN, UserRole.MANAGER)
  remove(@Param('id', ParseIntPipe) id: number) {
    return this.notifiersService.remove(id);
  }
}
