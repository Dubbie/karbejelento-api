import { User } from 'src/users/entities/user.entity';
import {
  Column,
  CreateDateColumn,
  Entity,
  ManyToOne,
  PrimaryGeneratedColumn,
} from 'typeorm';
import { Report, ReportStatus } from './report.entity';

@Entity('report_status_history')
export class ReportStatusHistory {
  @PrimaryGeneratedColumn()
  id: number;

  @Column()
  report_id: number;

  @ManyToOne(() => Report)
  report: Report;

  @Column({ nullable: true })
  user_id: number; // The user who made the change

  @ManyToOne(() => User)
  user: User;

  @Column({ type: 'enum', enum: ReportStatus })
  status: ReportStatus;

  @Column('text', { nullable: true })
  notes: string;

  @CreateDateColumn()
  created_at: Date;
}
