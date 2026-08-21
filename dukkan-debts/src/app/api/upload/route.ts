import { NextRequest, NextResponse } from "next/server";
import { nanoid } from "nanoid";
import { mkdir, writeFile } from "node:fs/promises";
import path from "node:path";
import { jsonError } from "@/lib/api-helpers";

const ALLOWED_TYPES: Record<string, string> = {
  "image/jpeg": "jpg",
  "image/png": "png",
  "image/webp": "webp",
  "image/gif": "gif",
  "audio/webm": "webm",
  "audio/ogg": "ogg",
  "audio/mpeg": "mp3",
  "audio/mp4": "m4a",
  "audio/wav": "wav",
};

const MAX_SIZE_BYTES = 15 * 1024 * 1024;

export async function POST(request: NextRequest) {
  const formData = await request.formData().catch(() => null);
  const file = formData?.get("file");

  if (!file || !(file instanceof File)) {
    return jsonError("لم يتم إرفاق أي ملف");
  }

  if (file.size > MAX_SIZE_BYTES) {
    return jsonError("حجم الملف كبير جداً (الحد الأقصى 15 ميغابايت)");
  }

  const extension = ALLOWED_TYPES[file.type];
  if (!extension) {
    return jsonError("نوع الملف غير مدعوم");
  }

  const uploadsDir = path.join(process.cwd(), "public", "uploads");
  await mkdir(uploadsDir, { recursive: true });

  const filename = `${nanoid(16)}.${extension}`;
  const buffer = Buffer.from(await file.arrayBuffer());
  await writeFile(path.join(uploadsDir, filename), buffer);

  return NextResponse.json({ url: `/uploads/${filename}` });
}
