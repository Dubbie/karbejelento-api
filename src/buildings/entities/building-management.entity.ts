import { User } from 'src/users/entities/user.entity';
import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  CreateDateColumn,
  ManyToOne,
  JoinColumn, // Import JoinColumn
} from 'typeorm';
import { Building } from './building.entity';

@Entity('building_management')
export class BuildingManagement {
  @PrimaryGeneratedColumn()
  id: number;

  @Column({ type: 'date' })
  start_date: Date;

  @Column({ type: 'date', nullable: true })
  end_date: Date | null;

  @CreateDateColumn()
  created_at: Date;

  @ManyToOne(() => Building, (building) => building.management_history)
  @JoinColumn({ name: 'building_id' })
  building: Building;

  @ManyToOne(() => User)
  @JoinColumn({ name: 'customer_id' })
  customer: User;
}
