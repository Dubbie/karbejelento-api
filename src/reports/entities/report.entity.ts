// src/reports/entities/report.entity.ts
import { Building } from 'src/buildings/entities/building.entity';
import { Notifier } from 'src/notifiers/entities/notifier.entity';
import { User } from 'src/users/entities/user.entity';
import {
  Column,
  CreateDateColumn,
  Entity,
  ManyToOne,
  PrimaryGeneratedColumn,
  Unique,
  UpdateDateColumn,
} from 'typeorm';

export enum ReportStatus {
  NEW = 'new',
  WAITING_FOR_DAMAGE_ID = 'waiting_for_damage_id',
  WAITING_FOR_INSURER = 'waiting_for_insurer',
  WAITING_FOR_DOCUMENTS = 'waiting_for_documents',
  IN_PROGRESS = 'in_progress',
  CLOSED_PAID = 'closed_paid',
  CLOSED_DECLINED = 'closed_declined',
  TEMP_CLOSED_INSPECTION = 'temp_closed_inspection',
  TEMP_CLOSED_DOCUMENTS = 'temp_closed_documents',
  DELETED = 'deleted',
  ARCHIVED = 'archived',
  REOPENED = 'reopened',
}

export enum ClaimantType {
  BUILDING = 'building',
  RESIDENT = 'resident',
}

@Entity('reports')
@Unique(['uuid'])
export class Report {
  @PrimaryGeneratedColumn()
  id: number;

  @Column({ type: 'char', length: 36 })
  uuid: string;

  @Column()
  building_id: number;

  @ManyToOne(() => Building)
  building: Building;

  @Column()
  created_by_user_id: number;

  @ManyToOne(() => User)
  created_by: User;

  @Column({ nullable: true })
  notifier_id: number;

  @ManyToOne(() => Notifier)
  notifier: Notifier;

  // Snapshot fields
  @Column()
  bond_number: string;

  @Column()
  insurer: string;

  @Column({ nullable: true, unique: true })
  damage_id: string;

  @Column()
  damage_type: string;

  @Column('text')
  damage_location_description: string;

  @Column({ type: 'date' })
  damage_date: Date;

  @Column({ type: 'decimal', precision: 12, scale: 2, nullable: true })
  estimated_cost: number;

  @Column({ type: 'enum', enum: ReportStatus, default: ReportStatus.NEW })
  current_status: ReportStatus;

  @Column({ type: 'enum', enum: ClaimantType })
  claimant_type: ClaimantType;

  @Column({ nullable: true })
  payment_method: string;

  // Resident claimant details
  @Column({ nullable: true })
  claimant_name: string;

  @Column({ nullable: true })
  claimant_email: string;

  @Column({ nullable: true })
  claimant_phone: string;

  @Column({ nullable: true })
  claimant_account_number: string;

  @CreateDateColumn()
  created_at: Date;

  @UpdateDateColumn()
  updated_at: Date;
}
