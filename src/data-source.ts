// src/data-source.ts

import { DataSource, DataSourceOptions } from 'typeorm';
import { config } from 'dotenv';
import { SeederOptions } from 'typeorm-extension';

config(); // Load .env file

export const dataSourceOptions: DataSourceOptions & SeederOptions = {
  type: 'mysql',
  host: process.env.DB_HOST,
  port: parseInt(process.env.DB_PORT || '3306', 10),
  username: process.env.DB_USERNAME,
  password: process.env.DB_PASSWORD,
  database: process.env.DB_DATABASE,
  entities: ['dist/**/*.entity.js'], // Important: Point to compiled JS files
  migrations: ['dist/db/migrations/*.js'],
  // This is where you will add the paths to your seeders and factories
  seeds: ['dist/db/seeds/*.js'],
  factories: ['dist/db/factories/*.js'],
};

const dataSource = new DataSource(dataSourceOptions);
export default dataSource;
