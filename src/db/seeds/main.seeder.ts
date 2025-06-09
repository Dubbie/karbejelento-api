import { Seeder, SeederFactoryManager } from 'typeorm-extension';
import { DataSource } from 'typeorm';
import { User, UserRole } from '../../users/entities/user.entity';
import * as bcrypt from 'bcrypt';

export default class MainSeeder implements Seeder {
  public async run(
    dataSource: DataSource,
    factoryManager: SeederFactoryManager,
  ): Promise<any> {
    const usersFactory = factoryManager.get(User);

    // Use a real hash for the password 'password'
    const password = 'password';
    const salt = await bcrypt.genSalt();
    const hashedPassword = await bcrypt.hash(password, salt);

    // ----- CREATE USERS -----
    console.log('Seeding users...');

    await usersFactory.save({
      name: 'Admin User',
      email: 'admin@example.com',
      role: UserRole.ADMIN,
      password_hash: hashedPassword,
    });

    const manager = await usersFactory.save({
      name: 'Manager User',
      email: 'manager@example.com',
      role: UserRole.MANAGER,
      password_hash: hashedPassword,
    });

    // Create a customer assigned to the manager we just created
    await usersFactory.save({
      name: 'Customer User',
      email: 'customer@example.com',
      role: UserRole.CUSTOMER,
      password_hash: hashedPassword,
      manager: manager,
    });

    console.log('Users seeded successfully!');
  }
}
