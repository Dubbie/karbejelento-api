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
import { UserRole } from 'src/users/entities/user.entity';

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
  findAll() {
    return this.buildingsService.findAll();
  }

  @Get(':uuid')
  findOne(@Param('uuid', ParseUUIDPipe) uuid: string) {
    return this.buildingsService.findOneByUuid(uuid);
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
