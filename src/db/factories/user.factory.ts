import { setSeederFactory } from 'typeorm-extension';
import { User, UserRole } from '../../users/entities/user.entity';
import { v4 as uuidv4 } from 'uuid';

export const UsersFactory = setSeederFactory(User, (faker) => {
  const user = new User();

  user.uuid = uuidv4();
  user.name = faker.person.fullName();
  user.email = faker.internet.email().toLowerCase();
  user.password_hash =
    '$2b$10$T9yZ/O.A.B.C.D.E.F.G.H.I.J.K.L.M.N.O.P.Q.R.S.T.U.V';

  // Default to customer role, can be overridden in the seeder
  user.role = UserRole.CUSTOMER;

  return user;
});
