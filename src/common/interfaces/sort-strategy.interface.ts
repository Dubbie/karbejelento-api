import { ObjectLiteral, SelectQueryBuilder } from 'typeorm';

export interface SortStrategy<T> {
  /**
   * Applies sorting logic to a TypeORM QueryBuilder.
   * @param qb The query builder instance.
   * @param alias The alias of the entity being sorted (e.g., 'user').
   * @param field The field name being sorted (e.g., 'role').
   * @param direction The sort direction ('ASC' or 'DESC').
   */
  apply(
    qb: SelectQueryBuilder<T extends ObjectLiteral ? T : any>,
    alias: string,
    field: string,
    direction: 'ASC' | 'DESC',
  ): void;
}
