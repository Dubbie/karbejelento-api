import {
  Controller,
  Get,
  Post,
  Body,
  Patch,
  Param,
  Delete,
  UseGuards,
  ParseUUIDPipe,
} from '@nestjs/common';
import { BuildingsService } from './buildings.service';
import { CreateBuildingDto } from './dto/create-building.dto';
import { UpdateBuildingDto } from './dto/update-building.dto';
import { AuthGuard } from '@nestjs/passport';
import { RolesGuard } from 'src/auth/guards/roles.guard';
import { Roles } from 'src/auth/decorators/roles.decorator';
import { User, UserRole } from 'src/users/entities/user.entity';
import { CurrentUser } from 'src/auth/decorators/current-user.decorator';

@Controller('buildings')
@UseGuards(AuthGuard('jwt'), RolesGuard)
export class BuildingsController {
  constructor(private readonly buildingsService: BuildingsService) {}

  @Post()
  @Roles(UserRole.ADMIN, UserRole.DAMAGE_SOLVER)
  create(@Body() createBuildingDto: CreateBuildingDto) {
    return this.buildingsService.create(createBuildingDto);
  }

  @Get()
  findAll(@CurrentUser() user: User) {
    return this.buildingsService.findAll(user);
  }

  @Get(':uuid')
  findOne(@Param('uuid', ParseUUIDPipe) uuid: string) {
    return this.buildingsService.findOneByUuid(uuid);
  }

  @Get(':id/notifiers')
  findNotifiersForBuilding(@Param('id') id: number) {
    return this.buildingsService.findNotifiersForBuilding(id);
  }

  @Patch(':uuid')
  @Roles(UserRole.ADMIN, UserRole.DAMAGE_SOLVER, UserRole.MANAGER)
  update(
    @Param('uuid', ParseUUIDPipe) uuid: string,
    @Body() updateBuildingDto: UpdateBuildingDto,
  ) {
    return this.buildingsService.update(uuid, updateBuildingDto);
  }

  @Delete(':uuid')
  @Roles(UserRole.ADMIN)
  remove(@Param('uuid', ParseUUIDPipe) uuid: string) {
    return this.buildingsService.remove(uuid);
  }
}
