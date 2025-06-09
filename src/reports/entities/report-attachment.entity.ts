import { User } from 'src/users/entities/user.entity';
import {
  Column,
  CreateDateColumn,
  Entity,
  ManyToOne,
  PrimaryGeneratedColumn,
  Unique,
} from 'typeorm';
import { Report } from './report.entity';

export enum AttachmentCategory {
  INVOICE = 'invoice',
  PHOTO = 'photo',
  DOCUMENT = 'document',
  OTHER = 'other',
}

@Entity('report_attachments')
@Unique(['uuid'])
export class ReportAttachment {
  @PrimaryGeneratedColumn()
  id: number;

  @Column({ type: 'char', length: 36 })
  uuid: string;

  @Column()
  report_id: number;

  @ManyToOne(() => Report, (report) => report.attachments)
  report: Report;

  @Column()
  uploaded_by_user_id: number;

  @ManyToOne(() => User)
  uploaded_by: User;

  @Column()
  file_path: string;

  @Column()
  file_name_original: string;

  @Column()
  file_mime_type: string;

  @Column()
  file_size_bytes: number;

  @Column({
    type: 'enum',
    enum: AttachmentCategory,
    default: AttachmentCategory.OTHER,
  })
  category: AttachmentCategory;

  @CreateDateColumn()
  created_at: Date;
}
