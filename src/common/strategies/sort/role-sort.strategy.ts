import { Injectable } from '@nestjs/common';
import { ObjectLiteral, SelectQueryBuilder } from 'typeorm';
import { UserRole } from '../../../users/entities/user.entity';
import { SortStrategy } from 'src/common/interfaces/sort-strategy.interface';

@Injectable()
export class RoleSortStrategy<T> implements SortStrategy<T> {
  // Define the desired order of roles. Index determines precedence.
  private readonly roleOrder: UserRole[] = [
    UserRole.ADMIN,
    UserRole.DAMAGE_SOLVER,
    UserRole.MANAGER,
    UserRole.CUSTOMER,
  ];

  apply(
    qb: SelectQueryBuilder<T extends ObjectLiteral ? T : any>,
    alias: string,
    field: string, // We know this will be 'role'
    direction: 'ASC' | 'DESC',
  ): void {
    const caseStatement = this.roleOrder
      .map((role, index) => `WHEN ${alias}.${field} = '${role}' THEN ${index}`)
      .join(' ');

    qb.addOrderBy(`CASE ${caseStatement} END`, direction);
  }
}
