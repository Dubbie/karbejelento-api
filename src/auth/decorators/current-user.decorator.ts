import { createParamDecorator, ExecutionContext } from '@nestjs/common';
import { User } from '../../users/entities/user.entity';
import { RequestWithUser } from '../types/request-with-user.interface';

/**
 * A custom parameter decorator to extract the user object from the request.
 * The JwtAuthGuard must be used on the route for this decorator to work.
 */
export const CurrentUser = createParamDecorator(
  (data: unknown, ctx: ExecutionContext): User => {
    const request = ctx.switchToHttp().getRequest<RequestWithUser>();
    // The user object is attached to the request by passport-jwt strategy
    return request.user;
  },
);
