# req_resp_test.py
import requests
import time
import os
from typing import Dict, List, Optional
from dataclasses import dataclass
from datetime import datetime

@dataclass
class TestResult:
    url: str
    method: str
    status_code: int
    response_time: float
    has_body: bool
    headers: Dict
    error: Optional[str] = None

class SiteTester:
    def __init__(self, base_url: str = "http://vlad.local"):
        self.base_url = base_url.rstrip('/')
        self.session = requests.Session()
        self.session.headers.update({
            'User-Agent': 'SiteTester/1.0'
        })
    
    def test_url(self, path: str, method: str = 'GET') -> TestResult:
        """Тестирует один URL"""
        url = f"{self.base_url}{path}"
        
        start_time = time.time()
        
        try:
            if method == 'HEAD':
                response = self.session.head(url, timeout=10, allow_redirects=True)
                has_body = len(response.content) > 0
            else:
                response = self.session.get(url, timeout=10, allow_redirects=True)
                has_body = True
            
            response_time = time.time() - start_time
            
            return TestResult(
                url=url,
                method=method,
                status_code=response.status_code,
                response_time=response_time,
                has_body=has_body,
                headers=dict(response.headers)
            )
            
        except Exception as e:
            return TestResult(
                url=url,
                method=method,
                status_code=0,
                response_time=time.time() - start_time,
                has_body=False,
                headers={},
                error=str(e)
            )
    
    def compare_head_get(self, path: str) -> Dict:
        """Сравнивает HEAD и GET запросы к одному URL"""
        print(f"\n{'='*60}")
        print(f"Тестируем: {path}")
        print('='*60)
        
        head_result = self.test_url(path, 'HEAD')
        time.sleep(0.5)  # Небольшая задержка между запросами
        get_result = self.test_url(path, 'GET')
        
        # Вывод результатов
        self._print_result(head_result, "HEAD")
        self._print_result(get_result, "GET")
        
        # Сравнение
        print(f"\n{'='*60}")
        print("СРАВНЕНИЕ HEAD vs GET:")
        print('='*60)
        
        issues = []
        
        # 1. Проверка статус кода
        if head_result.status_code != get_result.status_code:
            issues.append(f"❌ Разные статус коды: HEAD={head_result.status_code}, GET={get_result.status_code}")
        else:
            print(f"✅ Статус коды одинаковые: {head_result.status_code}")
        
        # 2. Проверка наличия тела у HEAD
        if head_result.has_body:
            issues.append("❌ HEAD возвращает тело (не должно!)")
        else:
            print("✅ HEAD не возвращает тело (правильно)")
        
        # 3. Проверка заголовков кэширования
        head_cache = head_result.headers.get('Cache-Control', '')
        get_cache = get_result.headers.get('Cache-Control', '')
        
        if head_cache != get_cache:
            issues.append(f"❌ Разный Cache-Control: HEAD='{head_cache}', GET='{get_cache}'")
        else:
            print(f"✅ Cache-Control одинаковый: {head_cache}")
        
        # 4. Проверка сессионных кук
        head_cookies = head_result.headers.get('Set-Cookie')
        get_cookies = get_result.headers.get('Set-Cookie')
        
        if head_cookies and 'PHPSESSID' in head_cookies:
            issues.append("❌ HEAD создает сессию (PHPSESSID)")
        else:
            print("✅ HEAD не создает сессию")
        
        # 5. Проверка X-Cache
        head_xcache = head_result.headers.get('X-Cache', '')
        get_xcache = get_result.headers.get('X-Cache', '')
        
        if head_xcache != get_xcache:
            print(f"⚠️  Разный X-Cache: HEAD='{head_xcache}', GET='{get_xcache}'")
        else:
            print(f"✅ X-Cache одинаковый: {head_xcache}")
        
        # Вывод проблем
        if issues:
            print(f"\n{'!'*60}")
            print("ОБНАРУЖЕНЫ ПРОБЛЕМЫ:")
            for issue in issues:
                print(f"  {issue}")
            print('!'*60)
        else:
            print("\n🎉 ВСЕ ПРОВЕРКИ ПРОЙДЕНЫ УСПЕШНО!")
        
        return {
            'head': head_result,
            'get': get_result,
            'issues': issues
        }
    
    def _print_result(self, result: TestResult, label: str):
        """Печатает результат теста"""
        print(f"\n{label} запрос:")
        print(f"  URL: {result.url}")
        
        if result.error:
            print(f"  ❌ Ошибка: {result.error}")
            return
        
        status_color = '✅' if 200 <= result.status_code < 300 else '❌'
        print(f"  {status_color} Статус: {result.status_code}")
        print(f"  ⏱️  Время ответа: {result.response_time:.2f} сек")
        
        # Важные заголовки
        important_headers = ['Cache-Control', 'X-Cache', 'Content-Type', 'Set-Cookie']
        for header in important_headers:
            if header in result.headers:
                value = result.headers[header]
                if header == 'Set-Cookie' and len(value) > 100:
                    value = value[:100] + '...'
                print(f"  📋 {header}: {value}")
    
    def test_multiple_urls(self, urls: List[str]):
        """Тестирует несколько URL"""
        print(f"\n{'#'*60}")
        print("ТЕСТИРОВАНИЕ НЕСКОЛЬКИХ URL")
        print('#'*60)
        
        all_results = {}
        
        for url in urls:
            all_results[url] = self.compare_head_get(url)
        
        # Сводка
        print(f"\n{'#'*60}")
        print("СВОДКА ПО ВСЕМ ТЕСТАМ:")
        print('#'*60)
        
        total_tests = len(urls)
        failed_tests = sum(1 for r in all_results.values() if r['issues'])
        
        if failed_tests == 0:
            print(f"🎉 ВСЕ {total_tests} ТЕСТОВ ПРОЙДЕНЫ УСПЕШНО!")
        else:
            print(f"⚠️  {failed_tests} из {total_tests} тестов имеют проблемы")
            
            for url, result in all_results.items():
                if result['issues']:
                    print(f"\nПроблемы с {url}:")
                    for issue in result['issues']:
                        print(f"  {issue}")

    # Добавь в конец класса SiteTester
    def generate_html_report(self, results: Dict, filename: str = "test_report.html"):
        """Генерирует HTML отчет"""
        html = """
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>QA Test Report - {site}</title>
            <style>
                body {{ font-family: Arial, sans-serif; margin: 20px; }}
                h1 {{ color: #333; }}
                .test {{ border: 1px solid #ddd; margin: 10px 0; padding: 15px; }}
                .passed {{ background-color: #d4edda; }}
                .failed {{ background-color: #f8d7da; }}
                .status-ok {{ color: green; }}
                .status-error {{ color: red; }}
                table {{ border-collapse: collapse; width: 100%; }}
                th, td {{ border: 1px solid #ddd; padding: 8px; text-align: left; }}
                th {{ background-color: #f2f2f2; }}
            </style>
        </head>
        <body>
            <h1>QA Test Report</h1>
            <p><strong>Site:</strong> {site}</p>
            <p><strong>Date:</strong> {date}</p>
            
            <h2>Summary</h2>
            <p>Total tests: {total_tests}<br>
            Passed: <span style="color:green">{passed}</span><br>
            Failed: <span style="color:red">{failed}</span></p>
            
            <h2>Test Results</h2>
        """.format(
            site=self.base_url,
            date=datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
            total_tests=len(results),
            passed=sum(1 for r in results.values() if not r['issues']),
            failed=sum(1 for r in results.values() if r['issues'])
        )
        
        for path, result in results.items():
            status_class = "passed" if not result['issues'] else "failed"
            
            html += f"""
            <div class="test {status_class}">
                <h3>{path}</h3>
                <p><strong>Status:</strong> <span class="status-{'ok' if result['head'].status_code == 200 else 'error'}">
                    HEAD={result['head'].status_code}, GET={result['get'].status_code}
                </span></p>
                <p><strong>Cache:</strong> {result['head'].headers.get('X-Cache', 'MISS')}</p>
            """
            
            if result['issues']:
                html += "<p><strong>Issues:</strong></p><ul>"
                for issue in result['issues']:
                    html += f"<li>{issue}</li>"
                html += "</ul>"
            else:
                html += "<p>✅ All checks passed</p>"
            
            html += "</div>"
        
        html += """
            <h2>Response Times</h2>
            <table>
                <tr>
                    <th>URL</th>
                    <th>HEAD Time</th>
                    <th>GET Time</th>
                    <th>Cache Status</th>
                </tr>
        """
        
        for path, result in results.items():
            html += f"""
                <tr>
                    <td>{path}</td>
                    <td>{result['head'].response_time:.2f}s</td>
                    <td>{result['get'].response_time:.2f}s</td>
                    <td>{result['head'].headers.get('X-Cache', 'MISS')}</td>
                </tr>
            """
        
        html += """
            </table>
        </body>
        </html>
        """
        
        with open(filename, 'w', encoding='utf-8') as f:
            f.write(html)
        
        print(f"\n📊 HTML отчет сохранен: {filename}")
        print(f"Открой в браузере: file://{os.path.abspath(filename)}")

# Пример использования
if __name__ == "__main__":
    tester = SiteTester(base_url="http://vlad.local")
    
    # Тестируемые URL
    test_urls = [
        "/page/o-proekte.html",
        "/",
        "/page/kontakty.html",
        "/robots.txt",
        "/sitemap.xml",
        "/css-generator.php?v=1770030372"
    ]
    
    # Тестируем все URL
    # tester.test_multiple_urls(test_urls)
    
    # Или тестируем один URL подробно
    # tester.compare_head_get("/page/o-proekte.html")

    # Запускаем тесты
    all_results = {}
    for url in test_urls:
        all_results[url] = tester.compare_head_get(url)

     # Генерируем отчет
    tester.generate_html_report(all_results, r"C:\Users\kriya\Projects\req_resp_test_report.html")
    
    print("\n" + "="*60)
    print("🎉 ТЕСТИРОВАНИЕ ЗАВЕРШЕНО УСПЕШНО!")
    print("="*60)