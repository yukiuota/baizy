const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

async function performanceCheck(url) {
  console.log(`🔍 パフォーマンスチェック開始: ${url}`);
  
  let browser;
  try {
    // Chromeを起動
    browser = await puppeteer.launch({
      headless: false,
      devtools: true,
      args: [
        '--no-sandbox',
        '--disable-setuid-sandbox',
        '--disable-dev-shm-usage',
        '--disable-web-security'
      ]
    });

    const page = await browser.newPage();
    
    // パフォーマンス情報を収集
    await page.setCacheEnabled(false);
    
    console.log('📊 パフォーマンストレース開始...');
    
    // トレースを開始
    await page.tracing.start({
      path: 'performance-trace.json',
      categories: ['devtools.timeline']
    });

    // ページ読み込み時間を測定
    const startTime = Date.now();
    
    try {
      const response = await page.goto(url, {
        waitUntil: ['networkidle0', 'load', 'domcontentloaded'],
        timeout: 30000
      });

      const loadTime = Date.now() - startTime;
      
      console.log(`✅ ページ読み込み完了: ${loadTime}ms`);
      console.log(`📡 ステータスコード: ${response.status()}`);

      // パフォーマンス情報を取得
      const performanceMetrics = await page.evaluate(() => {
        const timing = window.performance.timing;
        const navigation = window.performance.navigation;
        
        return {
          // 基本的なタイミング情報
          domContentLoaded: timing.domContentLoadedEventEnd - timing.navigationStart,
          loadComplete: timing.loadEventEnd - timing.navigationStart,
          domReady: timing.domContentLoadedEventStart - timing.navigationStart,
          
          // ネットワーク関連
          dnsLookup: timing.domainLookupEnd - timing.domainLookupStart,
          tcpConnection: timing.connectEnd - timing.connectStart,
          requestTime: timing.responseStart - timing.requestStart,
          responseTime: timing.responseEnd - timing.responseStart,
          
          // レンダリング関連
          domProcessing: timing.domComplete - timing.domLoading,
          
          // その他
          redirectTime: timing.redirectEnd - timing.redirectStart,
          navigationType: navigation.type,
          redirectCount: navigation.redirectCount,
          
          // Web Vitals approximation
          firstContentfulPaint: 'N/A', // より正確な測定のためには追加のAPIが必要
          largestContentfulPaint: 'N/A',
          cumulativeLayoutShift: 'N/A'
        };
      });

      // リソース情報を取得
      const resources = await page.evaluate(() => {
        return performance.getEntriesByType('resource').map(resource => ({
          name: resource.name,
          type: resource.initiatorType,
          size: resource.transferSize || 0,
          duration: resource.duration,
          startTime: resource.startTime
        }));
      });

      // トレース終了
      await page.tracing.stop();

      // 結果をまとめる
      const report = {
        url: url,
        timestamp: new Date().toISOString(),
        loadTime: loadTime,
        statusCode: response.status(),
        performanceMetrics: performanceMetrics,
        resourceCount: resources.length,
        totalTransferSize: resources.reduce((sum, r) => sum + r.size, 0),
        resources: resources.slice(0, 10), // 最初の10個のリソースのみ
        recommendations: generateRecommendations(performanceMetrics, resources)
      };

      // 結果をファイルに保存
      const reportPath = path.join(__dirname, 'performance-report.json');
      fs.writeFileSync(reportPath, JSON.stringify(report, null, 2));
      
      console.log('\n📋 パフォーマンスレポート:');
      console.log('====================');
      console.log(`⏱️  総読み込み時間: ${loadTime}ms`);
      console.log(`🚀 DOM読み込み時間: ${performanceMetrics.domContentLoaded}ms`);
      console.log(`📄 DOM処理時間: ${performanceMetrics.domProcessing}ms`);
      console.log(`🌐 DNS解決時間: ${performanceMetrics.dnsLookup}ms`);
      console.log(`🔗 TCP接続時間: ${performanceMetrics.tcpConnection}ms`);
      console.log(`📡 リクエスト時間: ${performanceMetrics.requestTime}ms`);
      console.log(`📥 レスポンス時間: ${performanceMetrics.responseTime}ms`);
      console.log(`📦 リソース数: ${resources.length}`);
      console.log(`💾 転送サイズ: ${Math.round(report.totalTransferSize / 1024)}KB`);
      
      console.log('\n💡 改善提案:');
      report.recommendations.forEach((rec, index) => {
        console.log(`${index + 1}. ${rec}`);
      });

      console.log(`\n📊 詳細レポート: ${reportPath}`);
      console.log(`🔍 パフォーマンストレース: performance-trace.json`);

    } catch (navigationError) {
      console.error(`❌ ページ読み込みエラー: ${navigationError.message}`);
      
      if (navigationError.message.includes('net::ERR_CONNECTION_REFUSED')) {
        console.log(`💡 ヒント: ${url} が利用できません。サーバーが起動しているか確認してください。`);
      }
    }

  } catch (error) {
    console.error(`❌ エラーが発生しました: ${error.message}`);
  } finally {
    if (browser) {
      await browser.close();
    }
  }
}

function generateRecommendations(metrics, resources) {
  const recommendations = [];
  
  if (metrics.domContentLoaded > 2000) {
    recommendations.push('DOM読み込み時間が遅いです。HTMLの構造を最適化し、重要でないJavaScriptの読み込みを延期してください。');
  }
  
  if (metrics.dnsLookup > 100) {
    recommendations.push('DNS解決が遅いです。DNSプリフェッチやCDNの使用を検討してください。');
  }
  
  if (resources.length > 100) {
    recommendations.push('リソース数が多すぎます。CSS/JSファイルの結合、画像の最適化を検討してください。');
  }
  
  const largeResources = resources.filter(r => r.size > 500000); // 500KB以上
  if (largeResources.length > 0) {
    recommendations.push(`大きなリソースがあります: ${largeResources.map(r => r.name.split('/').pop()).join(', ')}。圧縮や分割を検討してください。`);
  }
  
  if (recommendations.length === 0) {
    recommendations.push('パフォーマンスは良好です！現在の最適化を維持してください。');
  }
  
  return recommendations;
}

// コマンドライン引数からURLを取得
const targetUrl = process.argv[2] || 'http://localhost:10013/';
performanceCheck(targetUrl).catch(console.error);