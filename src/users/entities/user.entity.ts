import { Notifier } from 'src/notifiers/entities/notifier.entity';
import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  CreateDateColumn,
  UpdateDateColumn,
  Unique,
  ManyToOne,
  OneToMany,
  JoinColumn,
} from 'typeorm';

export enum UserRole {
  ADMIN = 'admin',
  DAMAGE_SOLVER = 'damage_solver',
  MANAGER = 'manager',
  CUSTOMER = 'customer',
}

@Entity('users')
@Unique(['email'])
@Unique(['uuid'])
export class User {
  @PrimaryGeneratedColumn()
  id: number;

  @Column({ type: 'char', length: 36 })
  uuid: string;

  @Column()
  name: string;

  @Column()
  email: string;

  @Column({ select: false })
  password_hash: string;

  @Column({
    type: 'enum',
    enum: UserRole,
  })
  role: UserRole;

  @Column({ default: true })
  is_active: boolean;

  @ManyToOne(() => User, (user) => user.customers, { nullable: true })
  @JoinColumn({ name: 'manager_id' })
  manager: User;

  @OneToMany(() => User, (user) => user.manager)
  customers: User[]; // A manager can have many customers

  @OneToMany(() => Notifier, (notifier) => notifier.customer)
  notifiers: Notifier[]; // A customer can have many notifiers

  @CreateDateColumn()
  created_at: Date;

  @UpdateDateColumn()
  updated_at: Date;
}
