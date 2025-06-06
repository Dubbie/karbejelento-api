import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  CreateDateColumn,
  UpdateDateColumn,
  Unique,
  ManyToOne,
  OneToMany,
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

  // This sets up the self-referencing relationship for managers and customers
  @ManyToOne(() => User, (user) => user.customers, { nullable: true })
  manager: User;

  @Column({ nullable: true })
  manager_id: number;

  @OneToMany(() => User, (user) => user.manager)
  customers: User[]; // A manager can have many customers

  @CreateDateColumn()
  created_at: Date;

  @UpdateDateColumn()
  updated_at: Date;
}
