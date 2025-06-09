// src/reports/entities/report.entity.ts
import { Building } from 'src/buildings/entities/building.entity';
import { Notifier } from 'src/notifiers/entities/notifier.entity';
import { User } from 'src/users/entities/user.entity';
import {
  Column,
  CreateDateColumn,
  Entity,
  JoinColumn,
  ManyToOne,
  OneToMany,
  PrimaryGeneratedColumn,
  Unique,
  UpdateDateColumn,
} from 'typeorm';
import { ReportAttachment } from './report-attachment.entity';

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

export enum DamageType {
  ROOF_LEAK = 'roof_leak',
  WINDOW_DAMAGE = 'window_damage',
  PIPE_BURST = 'pipe_burst',
  FIRE_DAMAGE = 'fire_damage',
  OTHER = 'other',
}

export enum EstimatedCost {
  RANGE_1 = '0-500',
  RANGE_2 = '501-2000',
  RANGE_3 = '2001-5000',
  RANGE_4 = '5001+',
}

@Entity('reports')
@Unique(['uuid'])
export class Report {
  @PrimaryGeneratedColumn()
  id: number;

  @Column({ type: 'char', length: 36 })
  uuid: string;

  // --- Core Relationships ---
  @ManyToOne(() => Building)
  @JoinColumn({ name: 'building_id' })
  building: Building;

  @ManyToOne(() => User)
  @JoinColumn({ name: 'created_by_user_id' })
  created_by: User;

  @ManyToOne(() => Notifier)
  @JoinColumn({ name: 'notifier_id' })
  notifier: Notifier;

  // --- Snapshot fields (from Building at time of creation) ---
  @Column()
  bond_number: string;

  @Column()
  insurer: string;

  // --- Damage Details (from Form) ---
  @Column({ nullable: true, unique: true })
  damage_id: string;

  @Column({ type: 'enum', enum: DamageType })
  damage_type: DamageType;

  @Column('text', { comment: 'General description of the damage' })
  damage_description: string;

  @Column({ nullable: true })
  damaged_building_name: string;

  @Column({ nullable: true })
  damaged_building_number: string;

  @Column({ nullable: true })
  damaged_floor: string;

  @Column({ nullable: true })
  damaged_unit_or_door: string;

  @Column({ type: 'date' })
  damage_date: Date;

  @Column({ type: 'enum', enum: EstimatedCost, nullable: true })
  estimated_cost: EstimatedCost;

  @Column({ type: 'enum', enum: ReportStatus, default: ReportStatus.NEW })
  current_status: ReportStatus;

  // --- Claimant & Contact Details ---
  @Column({ type: 'enum', enum: ClaimantType })
  claimant_type: ClaimantType;

  @Column({ nullable: true })
  claimant_name: string;

  @Column({ nullable: true })
  claimant_email: string;

  @Column({ nullable: true })
  claimant_phone_number: string;

  @Column({ nullable: true })
  contact_name: string;

  @Column({ nullable: true })
  contact_phone_number: string;

  @Column({ nullable: true })
  claimant_account_number: string;

  @OneToMany(() => ReportAttachment, (attachment) => attachment.report, {
    cascade: true,
  })
  attachments: ReportAttachment[];

  @CreateDateColumn()
  created_at: Date;

  @UpdateDateColumn()
  updated_at: Date;
}
