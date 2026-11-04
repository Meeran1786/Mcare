import { ImageManipulator, SaveFormat } from 'expo-image-manipulator';
import * as ImagePicker from 'expo-image-picker';

/** Phone photos are often 3-6 MB; the server takes at most 2 MB, so photos are shrunk to this width. */
const MAX_WIDTH = 1280;

/** Opens the camera or the gallery and returns the chosen photo's uri, or null. */
export async function pickImage(source: 'camera' | 'library'): Promise<string | null> {
  const perm =
    source === 'camera'
      ? await ImagePicker.requestCameraPermissionsAsync()
      : await ImagePicker.requestMediaLibraryPermissionsAsync();
  if (!perm.granted) return null;
  const options: ImagePicker.ImagePickerOptions = { mediaTypes: ['images'], quality: 0.6 };
  const res =
    source === 'camera' ? await ImagePicker.launchCameraAsync(options) : await ImagePicker.launchImageLibraryAsync(options);
  const asset = res.canceled ? null : res.assets[0];
  if (!asset) return null;
  return shrink(asset.uri, asset.width);
}

/** Several photos from the gallery (or one from the camera), each shrunk like pickImage's. */
export async function pickImages(source: 'camera' | 'library', max: number): Promise<string[]> {
  if (max <= 0) return [];
  if (source === 'camera') {
    const one = await pickImage('camera');
    return one ? [one] : [];
  }
  const perm = await ImagePicker.requestMediaLibraryPermissionsAsync();
  if (!perm.granted) return [];
  const res = await ImagePicker.launchImageLibraryAsync({
    mediaTypes: ['images'],
    quality: 0.6,
    allowsMultipleSelection: max > 1,
    selectionLimit: max,
  });
  if (res.canceled) return [];
  return Promise.all(res.assets.slice(0, max).map((a) => shrink(a.uri, a.width)));
}

/** A JPEG no wider than MAX_WIDTH, well under the 2 MB upload limit. */
async function shrink(uri: string, width: number): Promise<string> {
  try {
    const ctx = ImageManipulator.manipulate(uri);
    if (width > MAX_WIDTH) ctx.resize({ width: MAX_WIDTH });
    const image = await ctx.renderAsync();
    const saved = await image.saveAsync({ compress: 0.7, format: SaveFormat.JPEG });
    return saved.uri;
  } catch {
    return uri;
  }
}
