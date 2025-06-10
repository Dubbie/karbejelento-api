import { Injectable } from '@nestjs/common';
import { SortStrategy } from 'src/common/interfaces/sort-strategy.interface';
import { ObjectLiteral, SelectQueryBuilder } from 'typeorm';

@Injectable()
export class DefaultSortStrategy<T> implements SortStrategy<T> {
  apply(
    qb: SelectQueryBuilder<T extends ObjectLiteral ? T : any>,
    alias: string,
    field: string,
    direction: 'ASC' | 'DESC',
  ): void {
    qb.addOrderBy(`${alias}.${field}`, direction);
  }
}
