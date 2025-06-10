import { Module } from '@nestjs/common';
import { QueryScopeService } from './services/query-scope.service';

@Module({
  providers: [QueryScopeService],
  exports: [QueryScopeService],
})
export class CommonModule {}
