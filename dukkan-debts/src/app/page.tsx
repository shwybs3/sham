import { getPublicCustomers, getShopStats } from "@/lib/data";
import { getSettings, SETTINGS_KEYS, isTrue } from "@/lib/settings";
import { SiteHeader } from "@/components/site/SiteHeader";
import { SiteFooter } from "@/components/site/SiteFooter";
import { MarqueeBanner } from "@/components/site/MarqueeBanner";
import { StatsHero } from "@/components/site/StatsHero";
import { CustomerBrowser } from "@/components/site/CustomerBrowser";
import { ChatWidget } from "@/components/chat/ChatWidget";
import { EyeOff } from "lucide-react";

export const dynamic = "force-dynamic";

export default async function HomePage() {
  const settings = await getSettings();
  const shopName = settings[SETTINGS_KEYS.shopName];
  const publicPageEnabled = isTrue(settings[SETTINGS_KEYS.publicPageEnabled]);
  const bannerEnabled = isTrue(settings[SETTINGS_KEYS.bannerEnabled]);
  const bannerText = settings[SETTINGS_KEYS.bannerText];

  if (!publicPageEnabled) {
    return (
      <div className="flex min-h-screen flex-col">
        <SiteHeader shopName={shopName} />
        <div className="flex flex-1 flex-col items-center justify-center gap-4 px-4 text-center">
          <span className="flex size-16 items-center justify-center rounded-2xl bg-surface-muted text-muted">
            <EyeOff className="size-8" />
          </span>
          <h1 className="text-xl font-bold">الصفحة العامة غير مفعّلة حالياً</h1>
          <p className="max-w-sm text-sm text-muted">
            صاحب الدكان عطّل عرض الديون للزوار من لوحة الإدارة. إذا كنت تبحث عن حسابك تواصل مع الدكان مباشرة.
          </p>
        </div>
        <SiteFooter shopName={shopName} />
      </div>
    );
  }

  const [customers, stats] = await Promise.all([getPublicCustomers(), getShopStats()]);

  return (
    <div className="flex min-h-screen flex-col">
      <SiteHeader shopName={shopName} />
      {bannerEnabled && bannerText && <MarqueeBanner text={bannerText} />}

      <main className="mx-auto w-full max-w-6xl flex-1 px-4 py-8 sm:px-6">
        <div className="mb-8 animate-fade-up">
          <h1 className="text-2xl font-extrabold sm:text-3xl">دفتر الديون</h1>
          <p className="mt-1 text-muted">
            ابحث عن اسمك لمعرفة رصيد دينك وتفاصيل مشترياتك بكل سهولة وشفافية.
          </p>
        </div>

        <div className="mb-8">
          <StatsHero
            totalOutstanding={stats.totalOutstanding}
            debtorsCount={stats.debtorsCount}
            customersCount={stats.customersCount}
          />
        </div>

        <CustomerBrowser customers={customers} />
      </main>

      <SiteFooter shopName={shopName} />
      <ChatWidget />
    </div>
  );
}
