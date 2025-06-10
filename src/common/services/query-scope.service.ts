import { Injectable, ForbiddenException } from '@nestjs/common';
import { ObjectLiteral, SelectQueryBuilder } from 'typeorm';
import { User, UserRole } from '../../users/entities/user.entity';

@Injectable()
export class QueryScopeService {
  /**
   * Applies authorization scopes to a query that involves buildings.
   * @param qb The SelectQueryBuilder instance to modify.
   * @param user The authenticated user making the request.
   * @param buildingAlias The alias used for the 'Building' entity in the query builder (e.g., 'building', 'b').
   * @returns The modified SelectQueryBuilder instance for chaining.
   */
  applyBuildingScope<T>(
    qb: SelectQueryBuilder<T extends ObjectLiteral ? T : any>,
    user: User,
    buildingAlias: string,
  ): SelectQueryBuilder<T extends ObjectLiteral ? T : any> {
    // Dynamically create aliases for joined tables based on the building alias
    const historyAlias = `${buildingAlias}_management_history`;
    const customerAlias = `${historyAlias}_customer`;

    // Ensure the necessary relations are joined for the WHERE clauses to work.
    // The `.leftJoin` prevents TypeORM from adding the same join multiple times if it already exists.
    qb.leftJoin(`${buildingAlias}.management_history`, historyAlias).leftJoin(
      `${historyAlias}.customer`,
      customerAlias,
    );

    switch (user.role) {
      case UserRole.ADMIN:
      case UserRole.DAMAGE_SOLVER:
        // No restrictions, so we don't add any WHERE clauses.
        break;

      case UserRole.MANAGER:
        qb.andWhere(`${customerAlias}.manager_id = :managerId`, {
          managerId: user.id,
        }).andWhere(`${historyAlias}.end_date IS NULL`);
        break;

      case UserRole.CUSTOMER:
        qb.andWhere(`${customerAlias}.id = :customerId`, {
          customerId: user.id,
        }).andWhere(`${historyAlias}.end_date IS NULL`);
        break;

      default:
        // As a safeguard, deny access for any unhandled roles.
        throw new ForbiddenException(
          'You do not have permission to access this resource.',
        );
    }

    return qb;
  }
}
