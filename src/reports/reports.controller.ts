import {
  Controller,
  Get,
  Post,
  Body,
  Patch,
  Param,
  UseGuards,
  Req,
  ParseUUIDPipe,
  UnauthorizedException,
} from '@nestjs/common';
import { ReportsService } from './reports.service';
import { CreateReportDto } from './dto/create-report.dto';
import { UpdateReportDto } from './dto/update-report.dto';
import { AuthGuard } from '@nestjs/passport';
import { RolesGuard } from 'src/auth/guards/roles.guard';
import { Request } from 'express';
import { Roles } from 'src/auth/decorators/roles.decorator';
import { UserRole } from 'src/users/entities/user.entity';

@Controller('reports')
@UseGuards(AuthGuard('jwt'), RolesGuard)
export class ReportsController {
  constructor(private readonly reportsService: ReportsService) {}

  @Post()
  @Roles(
    UserRole.ADMIN,
    UserRole.DAMAGE_SOLVER,
    UserRole.MANAGER,
    UserRole.CUSTOMER,
  )
  create(@Body() createReportDto: CreateReportDto, @Req() req: Request) {
    const user = req.user;

    if (!user) {
      throw new UnauthorizedException('No user found on request');
    }

    return this.reportsService.create(createReportDto, user);
  }

  @Get()
  findAll() {
    return this.reportsService.findAll();
  }

  @Get(':uuid')
  findOne(@Param('uuid', ParseUUIDPipe) uuid: string) {
    return this.reportsService.findOneByUuid(uuid);
  }

  @Patch(':uuid')
  @Roles(UserRole.ADMIN, UserRole.DAMAGE_SOLVER, UserRole.MANAGER)
  update(
    @Param('uuid', ParseUUIDPipe) uuid: string,
    @Body() updateReportDto: UpdateReportDto,
  ) {
    return this.reportsService.update(uuid, updateReportDto);
  }
}
