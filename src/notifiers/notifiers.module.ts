import { Module } from '@nestjs/common';
import { NotifiersController } from './notifiers.controller';
import { NotifiersService } from './notifiers.service';
import { TypeOrmModule } from '@nestjs/typeorm';
import { Notifier } from './entities/notifier.entity';

@Module({
  imports: [TypeOrmModule.forFeature([Notifier])],
  controllers: [NotifiersController],
  providers: [NotifiersService],
})
export class NotifiersModule {}
