import { NextRequest, NextResponse } from "next/server";
import { z } from "zod";
import { prisma } from "@/lib/prisma";
import { jsonError, requireAdmin, isErrorResponse } from "@/lib/api-helpers";

export async function GET(request: NextRequest) {
  const adminView = request.nextUrl.searchParams.get("all") === "1";

  if (adminView) {
    const admin = await requireAdmin();
    if (isErrorResponse(admin)) return admin;
    const banners = await prisma.banner.findMany({ orderBy: { sortOrder: "asc" } });
    return NextResponse.json({ banners });
  }

  const banners = await prisma.banner.findMany({
    where: { active: true },
    orderBy: { sortOrder: "asc" },
  });
  return NextResponse.json({ banners });
}

const schema = z.object({
  imageUrl: z.string().trim().max(2000).optional().or(z.literal("")),
  linkUrl: z.string().trim().max(2000).optional().or(z.literal("")),
  text: z.string().trim().max(300).optional().or(z.literal("")),
  active: z.boolean().optional(),
  sortOrder: z.number().int().optional(),
});

export async function POST(request: NextRequest) {
  const admin = await requireAdmin();
  if (isErrorResponse(admin)) return admin;

  const body = await request.json().catch(() => null);
  const parsed = schema.safeParse(body);
  if (!parsed.success) return jsonError(parsed.error.issues[0]?.message ?? "بيانات غير صالحة");

  const banner = await prisma.banner.create({
    data: {
      imageUrl: parsed.data.imageUrl || null,
      linkUrl: parsed.data.linkUrl || null,
      text: parsed.data.text || null,
      active: parsed.data.active ?? true,
      sortOrder: parsed.data.sortOrder ?? 0,
    },
  });

  return NextResponse.json({ banner }, { status: 201 });
}
