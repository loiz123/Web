"""Browser regression for AJAX search, reset/history, and saved filters.

Requires Python Playwright and Chrome, or a Playwright Chromium installation.
Run: python tests/test_search_workflow.py --base http://127.0.0.1:8099
Uses a guest session; saved filters stay in this temporary browser context.
"""
import argparse
import os
import pathlib
from playwright.sync_api import sync_playwright, expect

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--base', default=os.environ.get('BDS_TEST_HTTP_BASE', 'http://127.0.0.1:8099'))
parser.add_argument('--chrome', default=os.environ.get('BDS_TEST_CHROME', r'C:\Program Files\Google\Chrome\Application\chrome.exe'))
args = parser.parse_args()
base = args.base.rstrip('/')

with sync_playwright() as pw:
    options = {'headless': True}
    if pathlib.Path(args.chrome).is_file():
        options['executable_path'] = args.chrome
    browser = pw.chromium.launch(**options)
    page = browser.new_page()
    page.set_default_timeout(15000)
    errors = []
    page.on('pageerror', lambda e: errors.append(str(e)))

    def go(path):
        response = page.goto(base + path, wait_until='domcontentloaded', timeout=60000)
        assert response.status == 200, (path, response.status)

    def is_search_json(response):
        return '/property/search?' in response.url and response.request.headers.get('accept') == 'application/json'

    go('/property/search')
    with page.expect_response(is_search_json, timeout=30000) as result:
        page.locator('[name=sort]').select_option('price_asc')
    payload = result.value.json()
    assert payload['success']
    prices = [float(p['price']) for p in payload['data']['properties']]
    assert prices == sorted(prices), prices
    expect(page).to_have_url(__import__('re').compile('sort=price_asc'))
    print('PASS changing sort refreshes AJAX results in ascending price order')

    go('/property/search?keyword=zzzz_missing_search_regression')
    expect(page.locator('#totalCountBadge')).to_have_text('0')
    page.locator('.btn-reset-filters').click()
    expect(page.locator('[name=keyword]')).to_have_value('')
    expect(page.locator('#totalCountBadge')).not_to_have_text('0', timeout=30000)
    page.go_back(wait_until='domcontentloaded')
    expect(page.locator('[name=keyword]')).to_have_value('zzzz_missing_search_regression')
    expect(page.locator('#totalCountBadge')).to_have_text('0', timeout=30000)
    page.go_forward(wait_until='domcontentloaded')
    expect(page.locator('[name=keyword]')).to_have_value('')
    expect(page.locator('#totalCountBadge')).not_to_have_text('0', timeout=30000)
    print('PASS reset removes loaded defaults and back/forward restores filters and results')

    page.locator('#btnSaveFilterModal').click()
    page.locator('#saveFilterNameInput').fill('Search regression filter')
    page.locator('#btnConfirmSaveFilter').click()
    expect(page.locator('.saved-filter-item')).to_have_count(1)
    go('/property/search?keyword=zzzz_missing_search_regression')
    item = page.locator('.saved-filter-item')
    expect(item).to_have_count(1)
    item.locator('.btn-apply-saved-filter').click()
    expect(page.locator('[name=keyword]')).to_have_value('')
    expect(page.locator('#totalCountBadge')).not_to_have_text('0', timeout=30000)
    item.locator('.btn-delete-saved-filter').click()
    page.locator('#bdsConfirmOkBtn').click()
    expect(item).to_have_count(0)
    go('/property/search')
    expect(page.locator('.saved-filter-item')).to_have_count(0)
    print('PASS guest filter save/reload/apply/delete persists correctly')

    page.set_viewport_size({'width': 390, 'height': 844})
    assert page.evaluate('document.documentElement.scrollWidth <= innerWidth + 1')
    assert not errors, errors
    print('PASS search page fits mobile width and has no JavaScript errors')
    browser.close()
