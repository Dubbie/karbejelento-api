import { Request } from 'express';
import { User } from '../../users/entities/user.entity';

/**
 * An interface to extend the default Express Request object.
 * This tells TypeScript that our request object will have a `user` property,
 * which is attached by our JwtAuthGuard after successful authentication.
 */
export interface RequestWithUser extends Request {
  user: User;
}
