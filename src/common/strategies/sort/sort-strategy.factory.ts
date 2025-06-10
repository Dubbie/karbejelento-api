import { Injectable, OnModuleInit } from '@nestjs/common';
import { ModuleRef } from '@nestjs/core';
import { DefaultSortStrategy } from './default-sort.strategy';
import { RoleSortStrategy } from './role-sort.strategy';
import { SortStrategy } from 'src/common/interfaces/sort-strategy.interface';

@Injectable()
export class SortStrategyFactory implements OnModuleInit {
  private strategies: Map<string, SortStrategy<any>>;

  constructor(private moduleRef: ModuleRef) {}

  onModuleInit() {
    this.strategies = new Map<string, SortStrategy<any>>();
    this.strategies.set(
      'role',
      this.moduleRef.get(RoleSortStrategy, { strict: false }),
    );
  }

  getStrategy(field: string): SortStrategy<any> {
    // If a specific strategy is registered for the field, use it.
    const strategy = this.strategies.get(field);
    if (strategy) {
      return strategy;
    }
    // Otherwise, fall back to the default sorting strategy.
    return this.moduleRef.get(DefaultSortStrategy, { strict: false });
  }
}
