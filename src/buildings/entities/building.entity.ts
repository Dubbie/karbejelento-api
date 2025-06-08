import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  CreateDateColumn,
  UpdateDateColumn,
  Unique,
  OneToMany,
  AfterLoad,
} from 'typeorm';
import { BuildingManagement } from './building-management.entity';
import { User } from 'src/users/entities/user.entity';

export enum StreetType {
  AVENUE = 'avenue',
  BOULEVARD = 'boulevard',
  CIRCLE = 'circle',
  PLACE = 'place',
  ROAD = 'road',
  SQUARE = 'square',
  STREET = 'street',
  TERRACE = 'terrace',
}

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

  @Column({ type: 'enum', enum: StreetType, nullable: true })
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

  current_customer: User | null;

  @CreateDateColumn()
  created_at: Date;

  @UpdateDateColumn()
  updated_at: Date;

  @AfterLoad()
  loadCurrentCustomer(): void {
    if (this.management_history && this.management_history.length > 0) {
      const currentManagement = this.management_history.find(
        (m) => m.end_date === null,
      );
      this.current_customer = currentManagement
        ? currentManagement.customer
        : null;
    } else {
      this.current_customer = null;
    }
  }
}
