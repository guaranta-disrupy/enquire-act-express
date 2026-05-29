// chunked-upload.js
import { useForm } from "@inertiajs/vue3";
import { ref } from "vue";

export const isDone = ref(false);
const cancelRequested = ref(false);

export function cancelUploads() {
    cancelRequested.value = true;
}

const chunkSize = 5 * 1024 * 1024; // 5MB chunks (5 * 1024 * 1024 bytes)
//const name = null

export async function initializeUpload(
    tempFile,
    params,
    web_route,
    img_id,
    progressContainerId,
    statusMessageId,
    imageStorage,
    displayImageEdit,
    onFileUploaded
) {
    const progressContainer = document.getElementById(progressContainerId);
    const statusMessage = document.getElementById(statusMessageId);
    let maxFile = 0;
    // Remove the "hidden" class if it exists
    if (progressContainer && progressContainer.classList.contains("hidden")) {
        progressContainer.classList.remove("hidden");
        // console.log("temp::: ", progressContainer);
    }

    if (statusMessage && statusMessage.classList.contains("hidden")) {
        statusMessage.classList.remove("hidden");
    }
    try {
        // console.log("temp::: ", tempFile.length);
        for (let i = 0; i < tempFile.length; i++) {
            if (cancelRequested.value) break;
            const file = tempFile[i];
            const { wrapper, bar } = createProgressBar(
                file,
                i,
                tempFile.length
            );
            progressContainer.appendChild(wrapper);
            // console.log("temp::: "+ i, progressContainer);
            await uploadFile(file, bar, wrapper);
        }
        // this is where the video is uploaded
        // statusMessage.textContent = "All uploads complete!";
        // Refresh the page or emit an event to update the video list
        // window.location.reload();
    } catch (error) {
        // statusMessage.textContent = "One or more uploads failed.";
        console.error("Upload error:", error);
    } finally {
        // Re-enable the file input and reset it
        // fileInput.disabled = false;
        // fileInput.value = "";
    }

    function randomString(length) {
        const characters =
            "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
        let result = "";
        const charactersLength = characters.length;
        for (let i = 0; i < length; i++) {
            result += characters.charAt(
                Math.floor(Math.random() * charactersLength)
            );
        }
        return result;
    }

    function createProgressBar(file, index, totalFiles) {
        const progressBarWrapper = document.createElement("div");
        progressBarWrapper.className = "mb-4";

        const fileNameDiv = document.createElement("div");
        fileNameDiv.className = "text-sm mb-1 truncate";
        fileNameDiv.textContent = `(${index + 1}/${totalFiles}) ${file.name}`;

        const progressBarContainer = document.createElement("div");
        progressBarContainer.className =
            "w-full bg-gray-200 dark:bg-gray-500 rounded-full";
        progressBarContainer.style.height = "14px";

        const progressBar = document.createElement("div");
        progressBar.style.backgroundColor = "#2563eb"; // A blue color
        progressBar.style.borderRadius = "8px"; // Equivalent to "rounded-full"
        progressBar.style.height = "14px"; // Height of the progress bar
        progressBar.style.width = "0%"; // Initial width
        progressBar.style.fontSize = "10px"; // Text size for the percentage
        progressBar.style.color = "#fff"; // White text
        progressBar.style.textAlign = "right";
        progressBar.style.paddingRight = "10px"; // Padding for text alignment

        progressBarContainer.appendChild(progressBar);
        progressBarWrapper.appendChild(fileNameDiv);
        progressBarWrapper.appendChild(progressBarContainer);

        return { wrapper: progressBarWrapper, bar: progressBar };
    }

    async function uploadFile(file, progressBar, progressBarWrapper) {
        //console.log('=====>>>', file)
        const totalChunks = Math.ceil(file.size / chunkSize);
        let currentChunk = 0;

        const form = useForm({
            file: null,
            name: randomString(32) + "." + file.name.split(".").pop(),
            chunk: 0,
            totalChunks: totalChunks,
            originalName: file.name,
            img_id: null,
        });

        return new Promise((resolve, reject) => {
            function uploadNextChunk() {
                if (cancelRequested.value) {
                    progressBarWrapper.style.display = "none";
                    return resolve();
                }
                const start = currentChunk * chunkSize;
                const end = Math.min(start + chunkSize, file.size);
                const chunk = file.slice(start, end);
                let success = false;
                form.file = chunk;
                form.chunk = currentChunk;
                form.img_id = img_id;
                form.post(route(web_route, params), {
                    forceFormData: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        //    alert("Upload successful");
                        success = true;
                    },
                    onFinish: () => {
                        if (cancelRequested.value) {
                            if (imageStorage) imageStorage.value = [];
                            if (displayImageEdit) displayImageEdit.value = null;
                            progressBarWrapper.style.display = "none";
                            return resolve();
                        }
                        if (!success) {
                            // alert("Upload failed");
                            if (imageStorage) {
                                imageStorage.value = [];
                            }
                            if (displayImageEdit) {
                                displayImageEdit.value = null;
                            }
                            progressBarWrapper.style.display = "none";
                            resolve();

                            return;
                        }
                        currentChunk++;
                        const progress = (currentChunk / totalChunks) * 100;
                        progressBar.style.width = progress + "%";
                        progressBar.style.textAlign = "right";
                        progressBar.style.paddingRight = "10px";
                        progressBar.textContent = Math.round(progress) + "%";

                        if (currentChunk < totalChunks) {
                            uploadNextChunk();
                        } else {
                            if (typeof onFileUploaded === "function") {
                                onFileUploaded(file);
                            }
                            maxFile++;
                            if (totalChunks === 1) {
                                setTimeout(() => {
                                    progressBarWrapper.style.display = "none";
                                    resolve();
                                }, 500); // Show 100% for 0.5s
                            } else {
                                progressBarWrapper.style.display = "none";
                                resolve();
                            }
                        }
                    },
                    onError: (error) => {
                        reject(error);
                    },
                });
            }
            uploadNextChunk();
        });
    }
    // console.log("dow:: ", maxFile)

    if (maxFile == tempFile.length || cancelRequested.value) {
        if (
            progressContainer &&
            !progressContainer.classList.contains("hidden")
        ) {
            progressContainer.classList.add("hidden");
        }

        if (statusMessage && !statusMessage.classList.contains("hidden")) {
            statusMessage.classList.add("hidden");
        }
        if (imageStorage) {
            imageStorage.value = [];
        }

        if (displayImageEdit) {
            displayImageEdit.value = null;
            maxFile = 0;
        }
        cancelRequested.value = false;
    }

    tempFile.value.addEventListener("change", async function (e) {
        const files = Array.from(e.target.files);
        // console.log("temp::: ", files)
        if (files.length === 0) return;

        // Clear previous progress bars
        progressContainer.innerHTML = "";
        statusMessage.textContent = "";

        try {
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const { wrapper, bar } = createProgressBar(
                    file,
                    i,
                    files.length
                );
                progressContainer.appendChild(wrapper);
                await uploadFile(file, bar, wrapper);
            }

            // this is where the video is uploaded
            // statusMessage.textContent = "All uploads complete!";
            // Refresh the page or emit an event to update the video list
            // window.location.reload();
        } catch (error) {
            statusMessage.textContent = "One or more uploads failed.";
            console.error("Upload error:", error);
        } finally {
            // Re-enable the file input and reset it
            // fileInput.disabled = false;
            // fileInput.value = ""; // Reset the file input
        }
    });
}
