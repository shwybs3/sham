import { NextRequest, NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";
import { requireAdmin, isErrorResponse } from "@/lib/api-helpers";

export async function GET(request: NextRequest) {
  const admin = await requireAdmin();
  if (isErrorResponse(admin)) return admin;

  const dateParam = request.nextUrl.searchParams.get("date");
  let where = {};
  if (dateParam) {
    const start = new Date(dateParam);
    start.setHours(0, 0, 0, 0);
    const end = new Date(start);
    end.setDate(end.getDate() + 1);
    where = { entryDate: { gte: start, lt: end } };
  }

  const entries = await prisma.breadEntry.findMany({
    where,
    include: { customer: { select: { id: true, name: true, email: true, avatarUrl: true } } },
    orderBy: { entryDate: "desc" },
    take: 300,
  });

  return NextResponse.json({ entries });
}
