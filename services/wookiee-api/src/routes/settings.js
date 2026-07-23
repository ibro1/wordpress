const express = require('express');
const store = require('../secretsStore');

const router = express.Router();

router.get('/', (req, res) => {
  res.json({ values: store.getAll() });
});

router.post('/', (req, res) => {
  const body = req.body && req.body.values ? req.body.values : {};
  const updated = store.setMany(body);
  res.json({ values: updated });
});

module.exports = router;
