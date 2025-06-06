import { Injectable, CanActivate, ExecutionContext } from '@nestjs/common';
import { Reflector } from '@nestjs/core';
import { UserRole } from 'src/users/entities/user.entity';
import { ROLES_KEY } from '../decorators/roles.decorator';
import { Request } from 'express';

@Injectable()
export class RolesGuard implements CanActivate {
  constructor(private reflector: Reflector) {}

  canActivate(context: ExecutionContext): boolean {
    // Get the required roles from the @Roles() decorator
    const requiredRoles = this.reflector.getAllAndOverride<UserRole[]>(
      ROLES_KEY,
      [context.getHandler(), context.getClass()],
    );

    // If no roles are required, allow access
    if (!requiredRoles) {
      return true;
    }

    // Get the user object from the request (attached by JwtAuthGuard)
    const request = context.switchToHttp().getRequest<Request>();
    const user = request.user;

    if (!user) {
      return false; // If there's no user, access is denied
    }

    return requiredRoles.some((role) => user.role === role);
  }
}
