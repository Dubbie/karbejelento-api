import { Module } from '@nestjs/common';
import { BuildingsController } from './buildings.controller';
import { BuildingsService } from './buildings.service';
import { TypeOrmModule } from '@nestjs/typeorm';
import { Building } from './entities/building.entity';
import { BuildingManagement } from './entities/building-management.entity';

@Module({
  imports: [TypeOrmModule.forFeature([Building, BuildingManagement])],
  controllers: [BuildingsController],
  providers: [BuildingsService],
})
export class BuildingsModule {}
