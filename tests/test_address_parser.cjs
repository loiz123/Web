// Run: node tests/test_address_parser.cjs
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

const detail = { value: 'Địa chỉ cũ, Phường An Khánh', dataset: {} };
const search = { value: '', dataset: {} };
const document = {
  readyState: 'loading',
  addEventListener() {},
  getElementById(id) { return id === 'address-detail' ? detail : id === 'mapSearchAddress' ? search : null; },
  querySelector() { return null; },
};
const context = { window: {}, document, setTimeout };
vm.createContext(context);
vm.runInContext(fs.readFileSync(path.join(__dirname, '../public/assets/js/address-parser.js'), 'utf8'), context);
const parser = context.window.BdsAddressParser;
const matched = {
  province: { name: 'TP. Hồ Chí Minh' },
  ward: { name: 'Phường An Khánh' },
};
const full = 'Khu phố 21, Phường An Khánh, Thành phố Hồ Chí Minh, 71108, Việt Nam';
const address = { quarter: 'Khu phố 21', suburb: 'Phường An Khánh', city: 'Thành phố Hồ Chí Minh', postcode: '71108', country: 'Việt Nam' };

assert.equal(parser.extractStreetAddress(full, address, matched), 'Khu phố 21');
assert.equal(parser.extractStreetAddress(full, { ...address, road: 'Khu phố 21, Phường An Khánh' }, matched), 'Khu phố 21');
assert.equal(parser.extractStreetAddress('12, Xuân Thủy, Phường An Khánh, TP. Hồ Chí Minh, Việt Nam', null, matched), '12 Xuân Thủy');
assert.equal(parser.extractStreetAddress('Xuân Thủy, Phường An Khánh, TP. Hồ Chí Minh', { road: 'Xuân Thủy', house_number: '12/4A' }, matched), '12/4A Xuân Thủy');
assert.equal(parser.extractStreetAddress('12 Xuân Thủy, Phường An Khánh, TP. Hồ Chí Minh', { road: '12 Xuân Thủy', house_number: '12' }, matched), '12 Xuân Thủy');
assert.equal(parser.extractStreetAddress('Xuân Thủy, Phường An Khánh, TP. Hồ Chí Minh', { road: 'Xuân Thủy' }, matched, { houseNumberHint: '12' }), '12 Xuân Thủy');
assert.equal(parser.extractStreetAddress('Thôn Đông, Xã Tân Phú, Tỉnh Đồng Nai, Việt Nam', { village: 'Thôn Đông', state: 'Tỉnh Đồng Nai' }, null), 'Thôn Đông');
assert.equal(parser.extractStreetAddress('Tòa A, 12 Xuân Thủy, Phường An Khánh, TP. Hồ Chí Minh', null, matched), 'Tòa A, 12 Xuân Thủy');
assert.equal(parser.extractStreetAddress('Phường An Khánh, TP. Hồ Chí Minh, 71108, Việt Nam', null, matched), '');

(async () => {
  await parser.handleReverseGeocode(full, address);
  assert.equal(detail.value, 'Khu phố 21', 'Replace the previous location, keeping its neighborhood');
  assert.equal(search.value, full, 'Keep the complete address in the map search field');
  await parser.handleReverseGeocode('Phường An Khánh, Thành phố Hồ Chí Minh, Việt Nam', address);
  assert.equal(detail.value, '', 'A location without a street must not retain the previous address');
  console.log('Address parser: 12 assertions passed');
})().catch(error => { console.error(error); process.exitCode = 1; });
