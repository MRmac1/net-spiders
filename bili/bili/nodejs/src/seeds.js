#! /usr/local/bin/node
const { Sequelize } = require('sequelize');
const { database: mysqlConfig, channels } = require('./config')

const sequelize = new Sequelize(`mysql://${mysqlConfig.user}:${mysqlConfig.password}@${mysqlConfig.host}:${mysqlConfig.port}/${mysqlConfig.db}`) // Postgres 示例

// 定义 Channel 表结构
const Channel = sequelize.define('channel', {
  id: {
    type: Sequelize.BIGINT(11),
    primaryKey: true
  },
  name: Sequelize.STRING(64),
  biliId: Sequelize.BIGINT(11),
  grade: Sequelize.STRING(10),
  route: Sequelize.STRING(64),
  pId: Sequelize.BIGINT(11),
  channel_desc: Sequelize.STRING(256)
}, {
  timestamps: false,
  tableName: 'Channel'
});

async function authenticate() {
  try {
    await sequelize.authenticate();
    console.log('Connection has been established successfully.');

    channels.forEach(async (mainChannel) => {
      const { tid: biliId, name, route, desc: channel_desc, sub } = mainChannel
      await Channel.create({
          name,
          biliId,
          route,
          channel_desc,
          grade: 1,
          pId: 0,
      });
      // 遍历二级目录
      if (sub && sub.length) {
        sub.forEach(async (item) => {
          const { tid: biliId, name, route: route2, desc: channel_desc } = item
          await Channel.create({
              name,
              biliId,
              route: `/v/${route}/${route2}`,
              channel_desc,
              grade: 2,
              pId: mainChannel.tid,
          });
        })
      }
    });
  } catch (error) {
    console.error('Unable to connect to the database:', error);
  }
}

authenticate()