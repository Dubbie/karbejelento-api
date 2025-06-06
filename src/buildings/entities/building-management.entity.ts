import { User } from 'src/users/entities/user.entity';
import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  CreateDateColumn,
  ManyToOne,
} from 'typeorm';
import { Building } from './building.entity';

@Entity('building_management')
export class BuildingManagement {
  @PrimaryGeneratedColumn()
  id: number;

  @Column()
  building_id: number;

  @Column()
  customer_id: number;

  @Column({ type: 'date' })
  start_date: Date;

  @Column({ type: 'date', nullable: true })
  end_date: Date | null;

  @CreateDateColumn()
  created_at: Date;

  // Many management records belong to one Building
  @ManyToOne(() => Building, (building) => building.management_history)
  building: Building;

  // Many management records belong to one Customer (User)
  @ManyToOne(() => User) // We don't need a reverse relation on User for this
  customer: User;
}
