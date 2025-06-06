import { Injectable } from '@nestjs/common';
import { UsersService } from 'src/users/users.service';
import { JwtService } from '@nestjs/jwt';
import * as bcrypt from 'bcrypt';
import { User } from 'src/users/entities/user.entity';

@Injectable()
export class AuthService {
  constructor(
    private usersService: UsersService,
    private jwtService: JwtService,
  ) {}

  /**
   * Validates a user's password.
   * @param email The user's email
   * @param pass The plain-text password to check
   * @returns The user object without the password hash if validation succeeds, otherwise null.
   */
  async validateUser(
    email: string,
    pass: string,
  ): Promise<Omit<User, 'password_hash'> | null> {
    const user = await this.usersService.findOneByEmail(email);

    if (!user) {
      return null;
    }

    // Explicitly await the comparison
    const isPasswordMatching = await bcrypt.compare(pass, user.password_hash);

    if (isPasswordMatching) {
      // eslint-disable-next-line @typescript-eslint/no-unused-vars
      const { password_hash, ...result } = user;
      return result;
    }

    return null;
  }

  /**
   * Generates a JWT for a given user.
   */
  login(user: Omit<User, 'password_hash'>) {
    const payload = { email: user.email, sub: user.uuid, role: user.role };
    return {
      access_token: this.jwtService.sign(payload),
    };
  }
}
