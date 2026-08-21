import { prisma } from "@/lib/prisma";
import { SETTINGS_KEYS, SETTINGS_DEFAULTS, isTrue } from "@/lib/settings-keys";

export { SETTINGS_KEYS, SETTINGS_DEFAULTS, isTrue };

export async function getSettings(): Promise<Record<string, string>> {
  const rows = await prisma.setting.findMany();
  const map: Record<string, string> = { ...SETTINGS_DEFAULTS };
  for (const row of rows) map[row.key] = row.value;
  return map;
}

export async function getSetting(key: string): Promise<string> {
  const row = await prisma.setting.findUnique({ where: { key } });
  return row?.value ?? SETTINGS_DEFAULTS[key] ?? "";
}

export async function setSettings(values: Record<string, string>): Promise<void> {
  await prisma.$transaction(
    Object.entries(values).map(([key, value]) =>
      prisma.setting.upsert({
        where: { key },
        update: { value },
        create: { key, value },
      })
    )
  );
}
