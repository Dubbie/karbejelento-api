import { Injectable } from '@nestjs/common';
import { ObjectLiteral, SelectQueryBuilder } from 'typeorm';
import { QueryDto } from '../dto/query.dto';
import { PaginationResult } from '../interfaces/pagination-result.interface';
import { SortStrategyFactory } from '../strategies/sort/sort-strategy.factory';

@Injectable()
export class PaginationService {
  constructor(private readonly sortStrategyFactory: SortStrategyFactory) {}

  /**
   * Paginates, sorts, and filters a query.
   * @param qb The SelectQueryBuilder to work with.
   * @param queryDto The validated query parameters.
   * @param entityAlias The alias for the main entity in the query (e.g., 'building', 'report').
   * @returns A promise resolving to a PaginationResult object.
   */
  async paginate<T>(
    qb: SelectQueryBuilder<T extends ObjectLiteral ? T : any>,
    queryDto: QueryDto,
    entityAlias: string,
    options: {
      sortableFields: string[];
      filterableFields: string[];
    },
  ): Promise<PaginationResult<T>> {
    // 1. Apply Filtering
    this.applyFiltering(
      qb,
      queryDto.filter,
      entityAlias,
      options.filterableFields,
    );

    // 2. Apply Sorting
    this.applySorting(qb, queryDto.sort, entityAlias, options.sortableFields);

    // 3. Get total count BEFORE pagination
    const totalItems = await qb.getCount();

    // 4. Apply Pagination
    const page = queryDto.page || 1;
    const limit = queryDto.limit || 10;
    const skip = (page - 1) * limit;

    qb.skip(skip).take(limit);

    // 5. Execute query
    const data = await qb.getMany();
    const itemCount = data.length;
    const totalPages = Math.ceil(totalItems / limit);

    return {
      data,
      meta: {
        totalItems,
        itemCount,
        itemsPerPage: limit,
        totalPages,
        currentPage: page,
      },
    };
  }

  private applySorting<T>(
    qb: SelectQueryBuilder<T extends ObjectLiteral ? T : any>,
    sort: string | undefined,
    alias: string,
    sortableFields: string[],
  ) {
    if (!sort) {
      qb.orderBy(`${alias}.created_at`, 'DESC');
      return;
    }

    const [field, direction] = sort.split(':');
    const validatedDirection = direction?.toUpperCase() as 'ASC' | 'DESC';

    if (
      !sortableFields.includes(field) ||
      !['ASC', 'DESC'].includes(validatedDirection)
    ) {
      return; // Security check
    }

    // Strategy pattern for custom sorting
    const strategy = this.sortStrategyFactory.getStrategy(field);
    strategy.apply(qb, alias, field, validatedDirection);
  }

  private applyFiltering<T>(
    qb: SelectQueryBuilder<T extends ObjectLiteral ? T : any>,
    filter: string | string[] | undefined,
    alias: string,
    filterableFields: string[],
  ) {
    if (!filter) {
      return;
    }

    const filters = Array.isArray(filter) ? filter : [filter];

    filters.forEach((f, index) => {
      const [field, op, value] = f.split(':');
      if (!field || !op || value === undefined) return; // Malformed filter

      if (!filterableFields.includes(field)) {
        return; // Do not filter if field is not in the whitelist
      }

      const paramName = `filter_${field}_${index}`;

      // Whitelist operators for security
      switch (op.toLowerCase()) {
        case 'eq':
          qb.andWhere(`${alias}.${field} = :${paramName}`, {
            [paramName]: value,
          });
          break;
        case 'neq':
          qb.andWhere(`${alias}.${field} != :${paramName}`, {
            [paramName]: value,
          });
          break;
        case 'like':
          qb.andWhere(`${alias}.${field} LIKE :${paramName}`, {
            [paramName]: `%${value}%`,
          });
          break;
        case 'in':
          // Assumes value is a comma-separated string, e.g., "new,in_progress"
          qb.andWhere(`${alias}.${field} IN (:...${paramName})`, {
            [paramName]: value.split(','),
          });
          break;
        // Add other operators as needed here (gt, lt, etc.)
      }
    });
  }
}
