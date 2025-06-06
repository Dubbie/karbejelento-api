import { Injectable, NotFoundException } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { CreateNotifierDto } from './dto/create-notifier.dto';
import { UpdateNotifierDto } from './dto/update-notifier.dto';
import { Notifier } from './entities/notifier.entity';

@Injectable()
export class NotifiersService {
  constructor(
    @InjectRepository(Notifier)
    private readonly notifierRepository: Repository<Notifier>,
  ) {}

  create(createNotifierDto: CreateNotifierDto) {
    const notifier = this.notifierRepository.create(createNotifierDto);
    return this.notifierRepository.save(notifier);
  }

  findAll() {
    return this.notifierRepository.find();
  }

  async findOne(id: number) {
    const notifier = await this.notifierRepository.findOneBy({ id });
    if (!notifier) {
      throw new NotFoundException(`Notifier with ID ${id} not found`);
    }
    return notifier;
  }

  async update(id: number, updateNotifierDto: UpdateNotifierDto) {
    const notifier = await this.notifierRepository.preload({
      id: id,
      ...updateNotifierDto,
    });
    if (!notifier) {
      throw new NotFoundException(`Notifier with ID ${id} not found`);
    }
    return this.notifierRepository.save(notifier);
  }

  async remove(id: number) {
    const result = await this.notifierRepository.delete(id);
    if (result.affected === 0) {
      throw new NotFoundException(`Notifier with ID ${id} not found`);
    }
  }
}
