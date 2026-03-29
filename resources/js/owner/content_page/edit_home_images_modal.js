/**
 * Home Images Modal Management
 * Handles uploading and managing home page images
 */
import HomeImageAPI from "../../api/homeImageApi.js";
import Toast from "../../utils/toast.js";

document.addEventListener("DOMContentLoaded", async () => {
    const grid = document.getElementById("homeImageGrid");
    const counter = document.getElementById("homeImageCounter");
    const cancelBtn = document.getElementById("cancelUpload");
    const modal = document.getElementById("editHomeImagesModal");
    const editHomeImageBtn = document.getElementById("editHomeImage");
    const saveBtn = document.querySelector(".home_image_save");

    const mainUploads = document.querySelector(".home_images_uploads");
    const emptyText = document.querySelector(".empty_home_images");

    const MAX = 5;

    let savedImages = [];
    let tempImages = [];

    // Load existing images from API on page load
    try {
        const images = await HomeImageAPI.getAllImages();
        savedImages = images.map((img) => ({
            id: img.id,
            image_path: img.image_path,
            preview: `/storage/${img.image_path}`, // Convert to accessible URL
        }));
        renderMainImages();
    } catch (error) {
        console.error("Failed to load home images:", error);
        Toast.error("Failed to load home images");
    }

    editHomeImageBtn.addEventListener("click", () => {
        tempImages = savedImages.map((img) => ({ ...img }));
        renderGrid();
        modal.style.display = "flex";
    });

    function updateCounter() {
        counter.textContent = `${tempImages.length} / ${MAX} images selected`;
    }

    function renderGrid() {
        grid.innerHTML = "";

        tempImages.forEach((fileObj, index) => {
            const wrapper = document.createElement("div");
            wrapper.className = "add_home_image_slot_wrapper";

            const slot = document.createElement("div");
            slot.className = "image_slot";

            const img = document.createElement("img");
            img.src = fileObj.preview;
            slot.appendChild(img);

            const clearBtn = document.createElement("button");
            clearBtn.className = "clear_image";
            clearBtn.textContent = "Remove";
            clearBtn.onclick = async () => {
                // If this is an existing image (has id), delete it from API
                if (fileObj.id) {
                    try {
                        await HomeImageAPI.deleteImage(fileObj.id);
                        Toast.success("Image removed");
                    } catch (error) {
                        Toast.error("Failed to remove image");
                        return;
                    }
                }

                tempImages.splice(index, 1);
                renderGrid();
            };

            wrapper.append(slot, clearBtn);
            grid.appendChild(wrapper);
        });

        if (tempImages.length < MAX) addPlusSlot();
        updateCounter();
    }

    function addPlusSlot() {
        const wrapper = document.createElement("div");
        wrapper.className = "add_home_image_slot_wrapper";

        const slot = document.createElement("div");
        slot.className = "image_slot plus";

        const input = document.createElement("input");
        input.type = "file";
        input.accept = ".jpg,.jpeg,.png,.gif,.webp";
        input.hidden = true;

        slot.onclick = () => input.click();

        input.onchange = () => {
            const file = input.files[0];
            if (!file || tempImages.length >= MAX) return;

            const reader = new FileReader();
            reader.onload = () => {
                tempImages.push({
                    file: file,
                    id: null, // Mark as new image (not yet uploaded)
                    preview: reader.result,
                });
                renderGrid();
            };
            reader.readAsDataURL(file);
        };

        wrapper.append(slot, input);
        grid.appendChild(wrapper);
    }

    function renderMainImages() {
        mainUploads.innerHTML = "";

        if (savedImages.length === 0) {
            emptyText.style.display = "block";
            return;
        }

        emptyText.style.display = "none";

        savedImages.forEach((imgObj) => {
            const box = document.createElement("div");
            box.className = "home_image_item";

            const img = document.createElement("img");
            img.src = imgObj.preview;

            box.appendChild(img);
            mainUploads.appendChild(box);
        });
    }

    saveBtn.onclick = async () => {
        try {
            saveBtn.disabled = true;
            saveBtn.textContent = "Saving...";

            // Upload new images (those without id)
            const newImages = tempImages.filter(
                (img) => !img.id && !img.image_path,
            );

            for (const imgObj of newImages) {
                const formData = new FormData();
                formData.append("image", imgObj.file);

                try {
                    const uploadedImage =
                        await HomeImageAPI.createImage(formData);
                    imgObj.id = uploadedImage.id;
                    imgObj.image_path = uploadedImage.image_path;
                    imgObj.preview = `/storage/${uploadedImage.image_path}`;
                } catch (error) {
                    Toast.error(`Failed to upload image: ${error.message}`);
                    return;
                }
            }

            savedImages = tempImages.map((img) => ({ ...img }));
            renderMainImages();
            modal.style.display = "none";
            Toast.success("Home images updated successfully!");
        } catch (error) {
            console.error("Error saving home images:", error);
            Toast.error("Failed to save home images");
        } finally {
            saveBtn.disabled = false;
            saveBtn.textContent = "Save Images";
        }
    };

    cancelBtn.onclick = () => {
        tempImages = [];
        modal.style.display = "none";
    };
});
