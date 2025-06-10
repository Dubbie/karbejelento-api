import {
  Controller,
  Get,
  Post,
  Body,
  Patch,
  Param,
  UseGuards,
  Req,
  ParseUUIDPipe,
  UnauthorizedException,
  UseInterceptors,
  UploadedFiles,
  BadRequestException,
} from '@nestjs/common';
import { ReportsService } from './reports.service';
import { CreateReportDto } from './dto/create-report.dto';
import { UpdateReportDto } from './dto/update-report.dto';
import { AuthGuard } from '@nestjs/passport';
import { RolesGuard } from 'src/auth/guards/roles.guard';
import { Request } from 'express';
import { Roles } from 'src/auth/decorators/roles.decorator';
import { User, UserRole } from 'src/users/entities/user.entity';
import { AnyFilesInterceptor } from '@nestjs/platform-express';
import { diskStorage } from 'multer';
import { extname } from 'path';
import { AttachmentCategory } from './entities/report-attachment.entity';
import { CurrentUser } from 'src/auth/decorators/current-user.decorator';

// Helper function for naming files to avoid collisions.
const editFileName = (
  req: any,
  file: { originalname: string },
  callback: (arg0: null, arg1: string) => void,
) => {
  const name = file.originalname.split('.')[0];
  const fileExtName = extname(file.originalname);
  const randomName = Array(16)
    .fill(null)
    .map(() => Math.round(Math.random() * 16).toString(16))
    .join('');
  callback(null, `${name}-${randomName}${fileExtName}`);
};

@Controller('reports')
@UseGuards(AuthGuard('jwt'), RolesGuard)
export class ReportsController {
  constructor(private readonly reportsService: ReportsService) {}

  @Post()
  @Roles(
    UserRole.ADMIN,
    UserRole.DAMAGE_SOLVER,
    UserRole.MANAGER,
    UserRole.CUSTOMER,
  )
  create(@Body() createReportDto: CreateReportDto, @Req() req: Request) {
    const user = req.user;

    if (!user) {
      throw new UnauthorizedException('No user found on request');
    }

    return this.reportsService.create(createReportDto, user);
  }

  @Get()
  findAll(@CurrentUser() user: User) {
    return this.reportsService.findAll(user);
  }

  @Get(':uuid')
  findOne(@Param('uuid', ParseUUIDPipe) uuid: string) {
    return this.reportsService.findOneByUuid(uuid);
  }

  @Post(':uuid/attachments')
  @UseInterceptors(
    AnyFilesInterceptor({
      storage: diskStorage({
        destination: './uploads',
        filename: editFileName,
      }),
    }),
  )
  uploadAttachments(
    @Param('uuid', ParseUUIDPipe) uuid: string,
    @UploadedFiles() files: Array<Express.Multer.File>,
    @Body() body: { attachments: { category: AttachmentCategory }[] },
    @Req() req: Request,
  ) {
    const user = req.user;
    if (!user) {
      throw new UnauthorizedException('No user found on request');
    }

    // 1. Get the attachment metadata array from the body.
    const attachmentMetadata = body.attachments;

    // 2. Validate that the arrays exist and have matching lengths.
    if (!attachmentMetadata || files.length !== attachmentMetadata.length) {
      throw new BadRequestException(
        `Mismatch: Received ${files.length} files but metadata for ${attachmentMetadata?.length || 0}.`,
      );
    }

    // 3. Extract the categories into a simple array.
    const categories = attachmentMetadata.map((item) => {
      const category = item.category;
      // Also validate each category against the enum here.
      if (!Object.values(AttachmentCategory).includes(category)) {
        throw new BadRequestException(`Invalid category provided: ${category}`);
      }
      return category;
    });

    // 4. We now have two perfectly parallel arrays: `files` and `categories`.
    // The service call is simple and clean.
    return this.reportsService.addAttachments(uuid, files, categories, user);
  }

  @Patch(':uuid')
  @Roles(UserRole.ADMIN, UserRole.DAMAGE_SOLVER, UserRole.MANAGER)
  update(
    @Param('uuid', ParseUUIDPipe) uuid: string,
    @Body() updateReportDto: UpdateReportDto,
  ) {
    return this.reportsService.update(uuid, updateReportDto);
  }
}
