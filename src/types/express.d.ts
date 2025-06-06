declare namespace Express {
  export interface Request {
    user?: Omit<
      import('../../src/users/entities/user.entity').User,
      'password_hash'
    >;
  }
}
