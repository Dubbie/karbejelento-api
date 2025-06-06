import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  CreateDateColumn,
  UpdateDateColumn,
  Unique,
  OneToMany,
} from 'typeorm';
import { BuildingManagement } from './building-management.entity';

@Entity('buildings')
@Unique(['uuid'])
@Unique(['bond_number'])
export class Building {
  @PrimaryGeneratedColumn()
  id: number;

  @Column({ type: 'char', length: 36 })
  uuid: string;

  @Column()
  name: string;

  @Column()
  postcode: string;

  @Column()
  city: string;

  @Column()
  street_name: string;

  @Column({ nullable: true })
  street_type: string;

  @Column()
  street_number: string;

  @Column()
  bond_number: string;

  @Column()
  account_number: string;

  @Column()
  insurer: string;

  @Column({ default: false })
  is_archived: boolean;

  // A Building can have many management history records
  @OneToMany(() => BuildingManagement, (management) => management.building)
  management_history: BuildingManagement[];

  @CreateDateColumn()
  created_at: Date;

  @UpdateDateColumn()
  updated_at: Date;
}
