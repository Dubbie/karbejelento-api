import { Module } from '@nestjs/common';
import { QueryScopeService } from './services/query-scope.service';
import { PaginationService } from './services/pagination.service';
import { DefaultSortStrategy } from './strategies/sort/default-sort.strategy';
import { RoleSortStrategy } from './strategies/sort/role-sort.strategy';
import { SortStrategyFactory } from './strategies/sort/sort-strategy.factory';

@Module({
  providers: [
    QueryScopeService,
    PaginationService,
    DefaultSortStrategy,
    RoleSortStrategy,
    SortStrategyFactory,
  ],
  exports: [QueryScopeService, PaginationService],
})
export class CommonModule {}
