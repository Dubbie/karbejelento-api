// src/users/users.service.ts

import {
  Injectable,
  NotFoundException,
  BadRequestException,
} from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { User, UserRole } from './entities/user.entity';
import { CreateUserDto } from './dto/create-user.dto';
import { UpdateUserDto } from './dto/update-user.dto';
import * as bcrypt from 'bcrypt';
import { v4 as uuidv4 } from 'uuid';

@Injectable()
export class UsersService {
  constructor(
    @InjectRepository(User)
    private readonly userRepository: Repository<User>,
  ) {}

  async create(
    createUserDto: CreateUserDto,
  ): Promise<Omit<User, 'password_hash'>> {
    const { email, password, role, manager_id, name } = createUserDto;

    const existingUser = await this.userRepository.findOneBy({ email });
    if (existingUser) {
      throw new BadRequestException('User with this email already exists');
    }

    if (role === UserRole.CUSTOMER && !manager_id) {
      throw new BadRequestException(
        'A customer must be assigned to a manager.',
      );
    }

    const salt = await bcrypt.genSalt();
    const hashedPassword = await bcrypt.hash(password, salt);

    const newUser = this.userRepository.create({
      name,
      email,
      role,
      manager_id,
      password_hash: hashedPassword,
      uuid: uuidv4(),
    });

    const savedUser = await this.userRepository.save(newUser);

    // This comment tells ESLint to ignore the unused 'password_hash' variable on the next line.
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    const { password_hash, ...result } = savedUser;
    return result;
  }

  findAll(): Promise<User[]> {
    return this.userRepository.find({
      relations: {
        manager: true,
      },
    });
  }

  async findOneByUuid(uuid: string): Promise<User> {
    const user = await this.userRepository.findOne({
      where: { uuid },
      relations: { manager: true, customers: true },
    });
    if (!user) {
      throw new NotFoundException(`User with UUID ${uuid} not found`);
    }
    return user;
  }

  async update(
    uuid: string,
    updateUserDto: UpdateUserDto,
  ): Promise<Omit<User, 'password_hash'>> {
    const existingUser = await this.userRepository.findOneBy({ uuid });
    if (!existingUser) {
      throw new NotFoundException(`User with UUID ${uuid} not found`);
    }

    const { password, ...otherFieldsToUpdate } = updateUserDto;
    this.userRepository.merge(existingUser, otherFieldsToUpdate);

    if (password) {
      const salt = await bcrypt.genSalt();
      existingUser.password_hash = await bcrypt.hash(password, salt);
    }

    const updatedUser = await this.userRepository.save(existingUser);

    // Same fix for the unused variable here.
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    const { password_hash, ...result } = updatedUser;
    return result;
  }

  async remove(uuid: string): Promise<void> {
    const result = await this.userRepository.delete({ uuid });

    if (result.affected === 0) {
      throw new NotFoundException(`User with UUID ${uuid} not found`);
    }
  }

  async findOneByEmail(email: string): Promise<User | null> {
    return this.userRepository
      .createQueryBuilder('user')
      .where('user.email = :email', { email })
      .addSelect('user.password_hash') // Explicitly select the hidden password field
      .getOne();
  }

  /**
   * Finds a single user (customer) by their ID and eagerly loads their notifiers.
   * @param customerId The ID of the customer.
   * @returns The User entity with the notifiers array populated.
   */
  async findNotifiersForCustomer(customerId: number): Promise<User> {
    const customer = await this.userRepository.findOne({
      where: { id: customerId },
      relations: {
        notifiers: true, // Eagerly load the notifiers relationship
      },
    });

    if (!customer) {
      throw new NotFoundException(`Customer with ID ${customerId} not found`);
    }

    return customer;
  }
}
